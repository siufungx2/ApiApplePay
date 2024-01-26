<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidateMerchantException;
use Illuminate\Http\Request;

class ApplePayController extends Controller
{
    public function validateMerchant(Request $request)
    {
        $url = $request->validateURL;
        $origin = $request->origin;
        $origin = parse_url($origin);
        if (!isset($origin['host'])) {
            $source = $origin['path'];
        } else {
            $source = $origin['host'];
        }

        $merchantSession =  [
            'merchantIdentifier' => env('APPLE_PAY_MERCHANT_ID'),
            'displayName' => 'ACY',
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
}
