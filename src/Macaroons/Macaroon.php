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

namespace Macaroons;

use Macaroons\Crypto;
use Macaroons\Exceptions;
use Macaroons\Serialization\V1;
use Macaroons\Serialization\Serializer;

/**
 * Class Macaroon
 * @package Macaroons
 */
final class Macaroon implements \IteratorAggregate
{
    /** @var string */
    private $location;

    /** @var string */
    private $identifier;

    /** @var string */
    private $signature;

    /** @var Caveats */
    private $caveats;

    /**
     * Macaroon constructor.
     *
     * @param string $location
     * @param string $identifier
     * @param string $signature
     * @param array  $caveats
     */
    public function __construct(string $location, string $identifier, string $signature, array $caveats = [])
    {
        $this->signature  = $signature;
        $this->identifier = $identifier;
        $this->location   = $location;
        $this->caveats    = new Caveats($caveats);
    }

    /**
     * @param string $location
     * @param string $identifier
     * @param string $secret
     *
     * @return Macaroon
     */
    public static function create(string $location, string $identifier, string $secret): Macaroon
    {
        $rootKey   = Crypto\gen_derived_key($secret);
        $signature = Crypto\hmac($rootKey, $identifier);

        Crypto\erase($secret);
        Crypto\erase($rootKey);

        return new static($location, $identifier, $signature);
    }

    /**
     * @return \Generator
     */
    public function getIterator(): \Generator
    {
        foreach ($this->caveats as $caveat) {
            yield $caveat;
        }
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     *
     * @return Macaroon
     */
    public function withSignature(string $signature): Macaroon
    {
        $copy = clone $this;
        $copy->signature = $signature;

        return $copy;
    }

    /**
     * @param string      $predicate
     * @param string|null $location
     *
     * @return Macaroon
     */
    public function withFirstPartyCaveat(string $predicate, string $location = null): Macaroon
    {
        $copy = clone $this;

        $caveat = new Caveat\FirstParty($predicate, $location);

        $copy->signature = $caveat->sign($this->signature);
        $copy->caveats   = $this->caveats->with($caveat);

        return $copy;
    }

    /**
     * @param string $secret    Third party secret
     * @param string $id        Caveat id
     * @param string $location  Third party location
     *
     * @return Macaroon
     */
    public function withThirdPartyCaveat(string $secret, string $id, string $location = null): Macaroon
    {
        $copy = clone $this;

        $caveat = Caveat\ThirdParty::create($id, $secret, $this->signature, $location);

        Crypto\erase($secret);

        $copy->signature = $caveat->sign($this->signature);
        $copy->caveats   = $this->caveats->with($caveat);

        return $copy;
    }

    /**
     * @return bool
     */
    public function hasCaveats(): bool
    {
        return !$this->caveats->isEmpty();
    }

    /**
     * Bind discharge macaroons to the authorizing macaroon
     *
     * @param Macaroon $discharge
     *
     * @return Macaroon
     */
    public function bind(Macaroon $discharge): Macaroon
    {
        $rootKey   = str_pad('', 32, "\0", STR_PAD_RIGHT);
        $signature = Crypto\bound_hmac($rootKey, $this->signature, $discharge->getSignature());

        Crypto\erase($rootKey);

        return $discharge->withSignature($signature);
    }

    /**
     * @param string   $secret
     * @param Verifier $verifier
     *
     * @return bool
     *
     * @throws \Macaroons\Exceptions\UnsatisfiedCaveat
     * @throws \Macaroons\Exceptions\InvalidSignature
     */
    public function verify(string $secret, Verifier $verifier): bool
    {
        // Initialize the verifier with the macaroon root signature
        $rootKey  = Crypto\gen_derived_key($secret);
        $verifier = $verifier->withSignature(Crypto\hmac($rootKey, $this->identifier));

        // Verify macaroon's caveats
        // It will also recursively verify discharge macaroons that match a third party caveat
        foreach ($this->caveats as $k => $caveat) {
            /** @var Caveat $caveat */
            $verifier = $caveat->verify($verifier, $this);
        }

        // Check whether the resulting signature stored in the verifier object
        // is equal to the final macaroon signature
        if (!hash_equals($this->signature, $verifier->getSignature())) {
            throw new Exceptions\InvalidSignature('The macaroon signature is not valid');
        }

        return true;
    }

    /**
     * @param string   $rootKey         Third party root key
     * @param Verifier $verifier
     * @param Macaroon $rootMacaroon
     *
     * @return bool
     *
     * @throws \Macaroons\Exceptions\UnsatisfiedCaveat
     * @throws \Macaroons\Exceptions\InvalidSignature
     */
    public function verifyAsDischarge(string $rootKey, Verifier $verifier, Macaroon $rootMacaroon): bool
    {
        // Initialize the verifier with the macaroon root signature
        $signature = Crypto\hmac($rootKey, $this->identifier);
        $verifier  = $verifier->withSignature($signature);

        // Verify macaroon's caveats
        foreach ($this->caveats as $caveat) {
            /** @var Caveat $caveat */
            $verifier = $caveat->verify($verifier, $this);
        }

        // Calculate the bound signature based on what the verifier has gathered
        $rootKey = str_pad('', 32, "\0", STR_PAD_RIGHT);
        $bound   = Crypto\bound_hmac($rootKey, $rootMacaroon->getSignature(), $verifier->getSignature());

        // Check whether the resulting bound signature is equal to the discharge macaroon signature
        if (!hash_equals($bound, $this->signature)) {
            throw new Exceptions\InvalidSignature(
                sprintf('The discharge macaroon with id \'%s\' signature is not valid', $this->identifier)
            );
        }

        return true;
    }

    /**
     * @param Serializer|null $serializer
     *
     * @return string
     */
    public function serialize(Serializer $serializer = null): string
    {
        $serializer = $serializer ?: new V1\Serializer();

        return $serializer->serialize($this);
    }

    /**
     * @param string          $data
     * @param Serializer|null $serializer
     *
     * @return Macaroon
     *
     * @throws \DomainException
     */
    public static function deserialize(string $data, Serializer $serializer = null): Macaroon
    {
        $serializer = $serializer ?: new V1\Serializer();

        return $serializer->deserialize($data);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            "location %s\nidentifier %s\n%ssignature %s\n",
            $this->location,
            $this->identifier,
            $this->caveats,
            Crypto\base64_url_encode($this->signature)
        );
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->caveats = clone $this->caveats;
    }
}
