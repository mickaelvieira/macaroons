# Macaroons

[![Software License](https://img.shields.io/github/license/mashape/apistatus.svg)](https://github.com/mickaelvieira/macaroons/blob/master/LICENSE.md)
[![Latest Stable Version](https://img.shields.io/packagist/v/mvieira/macaroons.svg)](https://packagist.org/packages/mvieira/macaroons)
[![Build Status](https://travis-ci.org/mickaelvieira/macaroons.svg?branch=master)](https://travis-ci.org/mickaelvieira/macaroons)
[![Coverage Status](https://coveralls.io/repos/github/mickaelvieira/macaroons/badge.svg?branch=master)](https://coveralls.io/github/mickaelvieira/macaroons?branch=master)

A php implementation of Macaroons: Cookies with Contextual Caveats for Decentralized Authorization in the Cloud

**Specification**
- [https://research.google.com/pubs/pub41892.html](https://research.google.com/pubs/pub41892.html)
- [https://github.com/rescrv/libmacaroons](https://github.com/rescrv/libmacaroons)

**Resources**
- [http://hackingdistributed.com/2014/05/21/my-first-macaroon/](http://hackingdistributed.com/2014/05/21/my-first-macaroon/)
- [https://air.mozilla.org/macaroons-cookies-with-contextual-caveats-for-decentralized-authorization-in-the-cloud/](https://air.mozilla.org/macaroons-cookies-with-contextual-caveats-for-decentralized-authorization-in-the-cloud/)
- [https://evancordell.com/2015/09/27/macaroons-101-contextual-confinement.html](https://evancordell.com/2015/09/27/macaroons-101-contextual-confinement.html)

## Installation

**Requirements**
- php >= 7.0
- [libsodium-php >= 1.0](https://github.com/jedisct1/libsodium-php)

**Note**:
- The `libsodium` library will be distributed with PHP >= 7.2)
- The `libsodium` library is not required in `composer.json` because the versions 1 (`ext-libsodium`) and 2 (`ext-sodium`) have different names. Nevertheless, this package should work with both once installed.

```json
{
    "require": {
        "mvieira/macaroons": "dev-master"
    }
}
```
or

```sh
$ composer require mvieira/macaroons
```

## Documentation

Here is a simple example with third party `macaroons`:

On the `target service` server, produce the `macaroon` authorizing the user to access the service.

```php
use Macaroons\Macaroon;

use function Macaroons\Crypto\crypto_gen_nonce;

$macaroon = Macaroon::create('secret random number', crypto_gen_nonce(), 'https://unicorn.co');
$macaroon = $macaroon
    ->withThirdPartyCaveat('third party secret', 'user_auth', 'https://auth.unicorn.co');

```

On the identification provider server, produce the `discharge macaroon` that will verified the `third party caveat`

```php
use Macaroons\Macaroon;

// user login happens beforehand...
// once the user manages to log in to the service

// Deserialize the root macaroon
$macaroon  = Macaroon::deserialize('@#!?$');

// prepare the discharge macaroon that will satisfied the third party caveat
$discharge = Macaroon::create('third party secret', 'user_auth', 'https://auth.unicorn.co')
    ->withFirstPartyCaveat('user_id = 12345678'); // add the requested first party caveat

// bind the discharge macaroon to the root macaroon
$discharge = $macaroon->bind($discharge);
```

Back on the target service server

```php
use Macaroons\Macaroon;
use Macaroons\Verifier;
use Macaroons\Serialization\V1\Serializer;

// deserialize both macaroons
$macaroon  = Macaroon::deserialize('@#!?$', new Serializer());
$discharge = Macaroon::deserialize('#?@$!', new Serializer());

// prepare the verifier
$verifier = (new Verifier())
    ->satisfyExact('user_id = 12345678')
    ->withDischargeMacaroon($discharge);


try {
    $verified = $macaroon->verify('secret random number', $verifier);
} catch (\DomainException $e) {
    // Catch verification errors
    echo $e->getMessage() . "\n";
}

```

## Examples

Examples are available in the directory ```./examples/```

```sh
$ php ./examples/1-target-service.php
```

```sh
$ php ./examples/2-identity-provider.php
```

```sh
$ php ./examples/3-verification.php
```

## Contributing

Please see [CONTRIBUTING](https://github.com/mickaelvieira/macaroons/tree/master/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [LICENSE](https://github.com/mickaelvieira/macaroons/tree/master/LICENSE.md)
for more information.
