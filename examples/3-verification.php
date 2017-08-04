<?php
declare(strict_types = 1);

include_once __DIR__ . '/../vendor/autoload.php';

use Macaroons\Macaroon;
use Macaroons\Verifier;

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$hash1 = 'MDAyMGxvY2F0aW9uIGh0dHBzOi8vdW5pY29ybi5jbwowMDI4aWRlbnRpZmllciCghGw5GGjiYyQSjs8aF0ZGo6Mw_ipxTk4KMDAxN2NpZCBpcCA9IDEyNy4wLjAuMQowMDEyY2lkIHVzZXJfYXV0aAowMDUxdmlkIOXyZ6eUCfULespnyinJyfwI91pWEFVDtsK7rd3jSwkqqMI7XcXe97U9LmL1NWYNGyFm-WQ4KALeXgo5BChxgY0bEFT33_t4ygowMDFmY2wgaHR0cHM6Ly9hdXRoLnVuaWNvcm4uY28KMDAyZnNpZ25hdHVyZSAzOX5zRFSGZ4_EXclWKzT-2nFBSIV0Mzbn7lIUb3PHRgo';
$hash2 = 'MDAyNWxvY2F0aW9uIGh0dHBzOi8vYXV0aC51bmljb3JuLmNvCjAwMTlpZGVudGlmaWVyIHVzZXJfYXV0aAowMDFiY2lkIHVzZXJfaWQgPSAxMjM0NTY3OAowMDJmc2lnbmF0dXJlIBP9UFbSFoqxmkmlw_bQ5J-KuhVQCBl9WGwggiQkqpCRCg';

$ipChecker = new class
{
    public function __invoke(string $predicate): bool
    {
        $result = preg_match('/^ip = ([\.0-9]+)$/', $predicate, $matches);
        return ($result === 0) ? false : $_SERVER['REMOTE_ADDR'] === $matches[1];
    }
};

// deserialize both macaroons
$macaroon  = Macaroon::deserialize($hash1);
$discharge = Macaroon::deserialize($hash2);

// build the verifier
$verifier = (new Verifier())
    ->satisfyExact('user_id = 12345678')
    ->satisfyGeneral($ipChecker)
    ->withDischargeMacaroon($discharge); // tell the verifier to use the discharge macaroon

try {
    // verify the macaroon
    $verified = $macaroon->verify('secret random number', $verifier);
} catch (\DomainException $e) {
    // catch eventual exception raised during the verification process
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

echo "Macaroon is verified!!!\n";
// Macaroon is verified, user can have access to the resource...
