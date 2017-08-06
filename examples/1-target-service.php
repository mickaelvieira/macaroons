<?php
declare(strict_types = 1);

include_once __DIR__ . '/../vendor/autoload.php';

use Macaroons\Macaroon;

use function Macaroons\Crypto\gen_nonce;

// on the target service server
// issue the macaroon with its caveats that need to be verified
// before giving access to the user

$macaroon = Macaroon::create('https://unicorn.co', gen_nonce(), 'secret random number');
$macaroon = $macaroon
    ->withFirstPartyCaveat('ip = 127.0.0.1')
    ->withThirdPartyCaveat('third party secret', 'user_auth', 'https://auth.unicorn.co');

// returns hash to the requesting user...
echo json_encode([
    'macaroon' => $macaroon->serialize(),
], JSON_PRETTY_PRINT);
