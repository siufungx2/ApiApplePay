<?php
namespace App\Services;
use App\Interfaces\IApplePayService;
use App\Exceptions\ValidateMerchantException;
use App\Exceptions\ValidateCertificatesException;
use App\Exceptions\UpdateOrderException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ApplePayServiceImpl implements IApplePayService
{
    private $certificate;
    private $publicKey;
    private $privateKey;
    private $rootCaCertificate;

    private const LEAF_CERTIFICATE_OID = '1.2.840.113635.100.6.29';
    private const INTERMEDIATE_CA_OID = '1.2.840.113635.100.6.2.14';

    private const STORAGE_SIGNATURE_PATH =  'app/signature/';

    public function __construct()
    {
        $this->certificate = config('ApplePay.certificates');
        $this->publicKey = config('ApplePay.publicKey');
        $this->privateKey = config('ApplePay.privateKey');
        $this->rootCaCertificate = config('ApplePay.rootCaCertificate');
    }

    public function validateMerchant($validateUrl, $requestUrl)
    {
        $url = $validateUrl;
        $origin = parse_url($requestUrl);
        if (!isset($origin['host'])) {
            $source = $origin['path'];
        } else {
            $source = $origin['host'];
        }

        $merchantSession =  [
            'merchantIdentifier' => env('APPLE_PAY_MERCHANT_ID'),
            'displayName' => 'ApplePay',
            'initiative' => 'web',
            'initiativeContext' => $source,
        ];

        try {
            $jsonPayload = json_encode($merchantSession);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate);
            curl_setopt($ch, CURLOPT_SSLKEY, $this->privateKey);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new ValidateMerchantException("Apple pay paymenSession request error " . curl_error($ch));
            }
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($statusCode == 200) {
                // validate merchant success and generate pending record in deposit fund table
                $responseData = json_decode($response, true);
                return response()->json($responseData);
            } else {
                throw new ValidateMerchantException("Apple Pay paymentSession request failed with status code: " . $statusCode);
            }
        } catch (\Exception $e) {
            throw new ValidateMerchantException("Apple pay paymenSession request error " . $e->getMessage());
        }
    }

    public function updateOrder($transactionId, $signature)
    {
        try {
            $validateSignatureResult = $this->validateSignature($transactionId, base64_decode($signature));
            return response()->json([
                'message' => 'Ok',
            ]);
            dd($validateSignatureResult);
            return response()->json([
                'message' => 'Update Fail',
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Excpetion $e) {
            throw new UpdateOrderException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Save signature in the file and run command
     * openssl pkcs7 -inform DER -in ./storage/certificates/$orderNumber -print_certs
     * @param string $orderNumber
     * @param base64_decode $signature
     */
    private function validateSignature($orderNumber, $signature)
    {
        // Signature string from payload
        $signatureFileName = $orderNumber;
        $signatureFilePath = self::STORAGE_SIGNATURE_PATH . $signatureFileName;
        Storage::disk('applyPaySignature')->put($orderNumber, $signature);

        // intermediateCertificatePath
        $intermediateCertificateFileName = $orderNumber . '-0';
        $intermediateCertificateFilePath = self::STORAGE_SIGNATURE_PATH . $intermediateCertificateFileName;

        // leafCertificatePath
        $leafCertificateFileName = $orderNumber . '-1';
        $leafCertificateFilePath = self::STORAGE_SIGNATURE_PATH . $leafCertificateFileName;

        $getCertificatesCommand = ['openssl', 'pkcs7', '-inform', 'DER', '-in', storage_path($signatureFilePath), '-print_certs'];
        try {
            $commandOutput = $this->runCommand($getCertificatesCommand);
            $certificates = str_replace("\n\n", "\n", $commandOutput);
            $certificates = str_replace("-----END CERTIFICATE-----\n", "-----END CERTIFICATE-----\n\n", $certificates);
            $certificates = explode("\n\n", $certificates);
            Storage::disk('applyPaySignature')->put($intermediateCertificateFileName, $certificates[0]);
            Storage::disk('applyPaySignature')->put($leafCertificateFileName, $certificates[1]);
        } catch (\Exception $e) {
            throw new ValidateCertificatesException("Can't get certificates", 0, $e);
        }
        $checkLeaf = $this->checkIfCertificateContainOID($certificates[0], self::LEAF_CERTIFICATE_OID);
        $checkCa = $this->checkIfCertificateContainOID($certificates[1], self::INTERMEDIATE_CA_OID);
        $validateCertificate = $this->validateCertificateChain(storage_path($intermediateCertificateFilePath), storage_path($leafCertificateFilePath));

        return $checkLeaf && $checkCa && $validateCertificate;
    }

    private function checkIfCertificateContainOID($certificate, $oid)
    {
        $certificateResource = @openssl_x509_read($certificate);
        if(empty($certificateResource)) {
            throw new ValidateCertificatesException("Can't load x509 certificate");
        }
        $certificateData = openssl_x509_parse($certificateResource, false);
        return $certificateData['extensions'][$oid] ?? false;
    }

    /**
     * @param string $intermediateCertificatePath
     * @param string $leafCertificatePath
     * @return bool
     * @throws \ValidateCertificatesException
     */
    public function validateCertificateChain($intermediateCertificatePath, $leafCertificatePath) {
        $verifyCertificateCommand = ['openssl', 'verify', '-CAfile', $this->rootCaCertificate, '-untrusted', $intermediateCertificatePath, $leafCertificatePath];

        try {
            $this->runCommand($verifyCertificateCommand);
        } catch (\Exception $e) {
            throw new ValidateCertificatesException("Can't validate certificate chain", 0, $e);
        }

        return true;
    }

    /**
     * @param array $command
     * @return string
     * @throws ProcessFailedException
     */
    private function runCommand(array $command)
    {
        $process = new Process($command);
        $process->mustRun();

        return $process->getOutput();
    }
}
