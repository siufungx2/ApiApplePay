<?php
/**
 * Direct apple pay related data.
 */
return [
    // FIles for get apple paymentSession via apple web
    'certificates' => storage_path('certificates/merchant_id.pem'),
    'privateKey' => storage_path('certificates/apple_pay_merchant_id.key'),
    'publicKey' => storage_path('certificates/apple_pay_public_key.pem'),
    'rootCaCertificate' => storage_path('certificates/AppleRootCA-G3.pem'),
];
