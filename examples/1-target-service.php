<?php
declare(strict_types = 1);

include_once __DIR__ . '/../vendor/autoload.php';

use Macaroons\Macaroon;

use function Macaroons\Crypto\crypto_gen_nonce;

// on the target service server
// issue the macaroon with its caveats that need to be verified
// before giving access to the user

$macaroon = Macaroon::create('https://unicorn.co', crypto_gen_nonce(), 'secret random number');
$macaroon = $macaroon
    ->withFirstPartyCaveat('ip = 127.0.0.1')
    ->withThirdPartyCaveat('third party secret', 'user_auth', 'https://auth.unicorn.co');

// returns hash to the requesting user...
echo json_encode([
    'macaroon' => $macaroon->serialize(),
], JSON_PRETTY_PRINT);

//MDAyMmxvY2F0aW9uIHNlY3JldCByYW5kb20gbnVtYmVyCjAwMjhpZGVudGlmaWVyIAW2pXouVimGAQwpISuZid9tCZgxts9IMwowMDE3Y2lkIGlwID0gMTI3LjAuMC4xCjAwMTJjaWQgdXNlcl9hdXRoCjAwNTF2aWQgD9Bm9q3Rgy4rJiDAYlJcSK8AFWjHJbrrKXM71tDAYqQhbzvxSF5-sZiz5p65qXQXqgU7dg7Wuy1yYtRiUEaOaFCV4fze3YX5CjAwMWZjbCBodHRwczovL2F1dGgudW5pY29ybi5jbwowMDJmc2lnbmF0dXJlIN53BDsF-4x57kF6qLciBHz9Ybtp-Ju0l0X3OzjmT7R2Cg
