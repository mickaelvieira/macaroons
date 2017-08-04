<?php
declare(strict_types = 1);

include_once __DIR__ . '/../vendor/autoload.php';

use Macaroons\Macaroon;

$hash = 'MDAyMGxvY2F0aW9uIGh0dHBzOi8vdW5pY29ybi5jbwowMDI4aWRlbnRpZmllciCghGw5GGjiYyQSjs8aF0ZGo6Mw_ipxTk4KMDAxN2NpZCBpcCA9IDEyNy4wLjAuMQowMDEyY2lkIHVzZXJfYXV0aAowMDUxdmlkIOXyZ6eUCfULespnyinJyfwI91pWEFVDtsK7rd3jSwkqqMI7XcXe97U9LmL1NWYNGyFm-WQ4KALeXgo5BChxgY0bEFT33_t4ygowMDFmY2wgaHR0cHM6Ly9hdXRoLnVuaWNvcm4uY28KMDAyZnNpZ25hdHVyZSAzOX5zRFSGZ4_EXclWKzT-2nFBSIV0Mzbn7lIUb3PHRgo';

// on the identification provider server

// user login happens beforehand
// if it is successful
// issue the discharge macaroon that will verify the third party caveat
$macaroon  = Macaroon::deserialize($hash);
$discharge = Macaroon::create('https://auth.unicorn.co', 'user_auth', 'third party secret')
    ->withFirstPartyCaveat('user_id = 12345678');

// bind it to the root macaroon
$discharge = $macaroon->bind($discharge);

// return both hashed macaroons to the requesting user
echo json_encode([
    'macaroon' => $macaroon->serialize(),
    'discharge' => $discharge->serialize(),
], JSON_PRETTY_PRINT);
