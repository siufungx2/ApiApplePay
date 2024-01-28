<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Interfaces\IApplePayService;
use App\Http\Requests\ValidateMerchantRequest;
use App\Http\Requests\UpdateOrderRequest;

class ApplePayController extends Controller
{
    public $applePayService;

    public function __construct(IApplePayService $applePayService)
    {
        $this->applePayService = $applePayService;
    }

    public function validateMerchant(ValidateMerchantRequest $request)
    {
        return $this->applePayService->validateMerchant($request->validateUrl, $request->origin);
    }

    public function updateOrder(UpdateOrderRequest $request)
    {
        $paymentDetails = $request->paymentDetails;
        return $this->applePayService->updateOrder($paymentDetails['token']['transactionIdentifier'], $paymentDetails['token']['paymentData']['signature']);
    }
}
