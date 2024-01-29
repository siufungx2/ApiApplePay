Fully implement the Apple Pay API directly.
Laravel API backend

[POST] /api/validate_merchant
[POST] /api/update_order

Additional information to create SSL in Docker Nginx

$ brew install mkcert nss

$ mkcert -install

$ mkcert -cert-file ./docker/nginx/localhost.pem -key-file ./docker/nginx/localhost-key.pem localhost
