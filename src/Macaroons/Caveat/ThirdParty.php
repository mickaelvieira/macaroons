<?php
/**
 * This file is part of Macaroons, a php implementation of Macaroons:
 * Cookies with Contextual Caveats for Decentralized Authorization in the Cloud
 *
 * (c) Mickaël Vieira <contact@mickael-vieira.com>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Macaroons\Caveat;

use Macaroons\Caveat;
use Macaroons\Macaroon;
use Macaroons\Verifier;
use Macaroons\Exceptions;

use function Macaroons\Crypto\crypto_encrypt;
use function Macaroons\Crypto\crypto_decrypt;
use function Macaroons\Crypto\base64_url_encode;
use function Macaroons\Crypto\crypto_bound_hmac;
use function Macaroons\Crypto\crypto_gen_derived_key;
use function Macaroons\Crypto\crypto_get_nonce_length;
use function Macaroons\Crypto\crypto_ensure_key_length;

/**
 * Class ThirdParty
 * @package Macaroons\Caveat
 */
final class ThirdParty extends Caveat
{
    /** @var string */
    private $caveatId;

    /**
     * ThirdParty constructor.
     *
     * @param string $caveatId
     * @param string $verificationId
     * @param string $location
     */
    public function __construct(string $caveatId, string $verificationId, string $location = null)
    {
        $this->caveatId       = $caveatId;
        $this->verificationId = $verificationId;
        $this->location       = $location;
    }

    /**
     * @param string $id            Caveat id
     * @param string $secret        Third party secret
     * @param string $signature     Root macaroon's signature
     * @param string $location      Third party location
     *
     * @return ThirdParty
     */
    public static function create(string $id, string $secret, string $signature, string $location = null): ThirdParty
    {
        return new self($id, self::calculateVerificationId($secret, $signature), $location);
    }

    /**
     * {@inheritdoc}
     */
    public function getCaveatId(): string
    {
        return $this->caveatId;
    }

    /**
     * {@inheritdoc}
     */
    public function sign(string $secret): string
    {
        return crypto_bound_hmac($secret, $this->verificationId, $this->caveatId);
    }

    /**
     * {@inheritdoc}
     */
    public function verify(Verifier $verifier, Macaroon $root): Verifier
    {
        // Is there a discharge macaroon matching this caveat?
        $discharge = $verifier->retrieveDischargeMacaroonVerifyingThisCaveat($this);
        if (!$discharge) {
            throw new Exceptions\UnsatisfiedCaveat(sprintf('Caveat \'%s\' is not satisfied', $this->caveatId));
        }

        // It will trigger an exception if it cannot retrieve the root key
        $rootKey = $this->retrieveRootKeyFromVerificationId($verifier->getSignature());

        // We pass the same verifier as it will be reinitialized with the discharge macaroon root signature
        // which will be calculated using the root key
        $discharge->verifyAsDischarge($rootKey, $verifier, $root);

        // Pass on the signature to the next caveat
        return $verifier->withSignature($this->sign($verifier->getSignature()));
    }

    /**
     * @param string $secret        Third party secret
     * @param string $signature     Macaroon's signature
     *
     * @return string
     */
    public static function calculateVerificationId(string $secret, string $signature): string
    {
        $rootKey = crypto_gen_derived_key($secret);

        return crypto_encrypt($rootKey, crypto_ensure_key_length($signature));
    }

    /**
     * @param string $signature
     *
     * @return string
     *
     * @throw \LogicException
     */
    public function retrieveRootKeyFromVerificationId(string $signature): string
    {
        $nonce = substr($this->verificationId, 0, crypto_get_nonce_length());
        $vId   = substr($this->verificationId, crypto_get_nonce_length());

        return crypto_decrypt($vId, $nonce, crypto_ensure_key_length($signature));
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'cid' => $this->caveatId,
            'vid' => $this->verificationId,
            'cl'  => $this->location,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return sprintf(
            "cid %s\nvid %s\n%s",
            $this->caveatId,
            base64_url_encode($this->verificationId),
            $this->hasLocation() ? sprintf("cl %s\n", $this->getLocation()) : ''
        );
    }
}
