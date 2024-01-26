<?php
namespace App\Interfaces;

interface IApplePayService
{
    /**
     * CURL request to validate merchant from apple server and return apple session
     * @param string $validateUrl
     * @param string $requestUrl
     * @return object appleSession - from apple server
     * @throws \ValidateMerchantException
     */
    public function validateMerchant($validateUrl, $requestUrl);
}
