<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Interfaces\IApplePayService;
use Mockery;

class ApplePayTest extends TestCase
{
    private const ORIGIN = 'test.applepayapi.local';

    public function test_validate_merchant(): void
    {
        $validateSuccessReturn = [
            "epochTimestamp" => 1706487411310,
            "expiresAt" => 1706491011310,
            "merchantSessionIdentifier" => "FakeMSID",
            "nonce" => "a89edb02",
            "merchantIdentifier" => "MID",
            "domainName" => self::ORIGIN,
            "displayName" => "ApplePay",
            "signature" => "FakseSignature",
            "operationalAnalyticsIdentifier" => "ApplePay:FakePSPID",
            "retries" => 0,
            "pspId" => "FakePSPID"
        ];

        $mockApplePayService = Mockery::mock(IApplePayService::class)->makePartial();

        $mockApplePayService->shouldReceive('validateMerchant')->andReturn(response()->json($validateSuccessReturn));

        $this->app->instance(IApplePayService::class, $mockApplePayService);
        $headers = [
            'origin' => self::ORIGIN,
        ];

        $payload = [
            "validateUrl" => "https://apple-pay-gateway.apple.com/paymentservices/startSession",
        ];

        $response = $this->withHeaders($headers)->json('POST', '/api/validate_merchant', $payload);

        $response->assertStatus(200);
    }

    public function test_update_order(): void
    {
        $mockApplePayService = Mockery::mock(IApplePayService::class)->makePartial();

        $mockApplePayService->shouldReceive('updateOrder')->andReturn(response()->json([
            'message' => 'Ok',
        ]));

        $this->app->instance(IApplePayService::class, $mockApplePayService);

        $payload = [
          "paymentDetails" => [
                "token" => [
                   "paymentData" => [
                      "data" => "b+kb34d6RGviJOK4W1yMX2wFYQJbcwtdAIqtHTK5LY1mkXeUlUNOWKZ8jg9wzuIoDvKbfvLCyvYEjQQBTaN9Zc41E/S24A4CLraUAVqc5/4eYhmIzGfS6JzYNWGSOUN+Mr/NxG2cXcij51aztYui9mFp9lG5ivPClyIX026SOu3cUcjQ9w8C29MMqH4xJiczy+khu2bX4NFgRBKoO2JCaWU5isxUjdKMdRgohcB9DG7nYHvNrwQtYDuQf17zvXNFfWXETUwPbopFhAUsyHp9TF2mUVhDy2uGN29bhXtrPy9B0o4tsVOkftL+OImMicTxnUBKekvWmcxM/S23niyMv9InbmenEaM1ogcvAl4wlAi8fBAVvcXITrfLEAkAU2+OBghg7/jACvK4QnBu",
                      "signature" => "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",
                      "header" => [
                         "publicKeyHash" => "WCyAu2ctNQJ5M0C2wuSL9i/rqtNETZSsM+odo3S43iA=",
                         "transactionId" => "a9f0ecdb64f1829bc179f5fec9495fb26c3f75892984acd3c40696bd01a6be22",
                         "wrappedKey" => "szfXZ1Lokkj0fYC2EVOZFaUm41d+OkalLcqxHN3KQRhSuUBx3r8SFI8Bdl3QtBScfdDdQzESVR4Cy/5mhmswV79gnE5D2NqADwDWJ3isLrU27RmJuxDudeosw/4aW0kZWy3L4UbOZAWasQDjjiQQGxxGKdm4FhWLTGFjlVNi6WRye4FxpMoV9WgyLz2/N2o3lUQ3WkAMhw47/IMplsCa31PihuaTd2sJ3A/mmeNC+S8f6qgBx8GJTfjaHyRgwU9ajBmBu9mRD7r/kLY01U9zDGQCjIvuVaLlVTVRt5dz7FsNsQQFYWm2phlYZo5ZotrCWt1pyXfhMhiLpRZguRg0Jg=="
                      ],
                      "version" => "RSA_v1"
                   ],
                   "paymentMethod" => [
                            "displayName" => "MasterCard 0049",
                            "network" => "MasterCard",
                            "type" => "credit"
                         ],
                   "transactionIdentifier" => "a9f0ecdb64f1829bc179f5fec9495fb26c3f75892984acd3c40696bd01a6be22"
                ]
             ]
       ];

        $response = $this->json('POST', '/api/update_order', $payload);

        $response->assertStatus(200);
    }
}
