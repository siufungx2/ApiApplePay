Fully implement the Apple Pay API directly.
Laravel API backend

[POST] /api/validate_merchant
[POST] /api/update_order

Certificate for apple pay (apple developer only portal)
Csr for upload to apple developer (Private key command)

`openssl req -new -newkey rsa:2048 -nodes -keyout apple_pay_merchant_id.key -out merchant_id.csr`
 
DOWNLOAD from apple developer
 
 
`openssl x509 -inform der -in merchant_id.cer -out merchant_id.pem`
 
Public key command
 
 
`openssl x509 -inform DER -pubkey -noout -in merchant_id.cer -out apple_pay_public_key.pem`
 
Apple Root CA-G3 Cert (Come with .cer need to convert to pem so php can read)
 
`openssl x509 -inform der -in AppleRootCA-G3.cer -out AppleRootCA-G3.pem`
 

Additional information to create SSL in Docker Nginx

$ brew install mkcert nss

$ mkcert -install

$ mkcert -cert-file ./docker/nginx/localhost.pem -key-file ./docker/nginx/localhost-key.pem localhost
