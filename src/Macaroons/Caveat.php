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

/**
 * Interface Caveat
 * @package Macaroons
 */
abstract class Caveat
{
    /** @var string */
    protected $verificationId;

    /** @var string */
    protected $location;

    /**
     * @return string
     */
    abstract public function getCaveatId(): string;

    /**
     * @param string $secret    Macaroon's signature
     *
     * @return string
     */
    abstract public function sign(string $secret): string;

    /**
     * @param Verifier $verifier
     * @param Macaroon $root        Root macaroon
     *
     * @return Verifier
     *
     * @throws \Macaroons\Exceptions\UnsatisfiedCaveat
     * @throws \Macaroons\Exceptions\InvalidSignature
     */
    abstract public function verify(Verifier $verifier, Macaroon $root): Verifier;

    /**
     * @param array $data
     *
     * @return Caveat
     */
    public static function fromArray(array $data): Caveat
    {
        return array_key_exists('vid', $data)
            ? new Caveat\ThirdParty($data['cid'], $data['vid'], $data['cl'] ?? null)
            : new Caveat\FirstParty($data['cid'], $data['cl'] ?? null);
    }

    /**
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * @return string
     */
    public function getVerificationId(): string
    {
        return $this->verificationId;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @return bool
     */
    public function hasLocation(): bool
    {
        return is_string($this->location);
    }

    /**
     * @return string
     */
    abstract public function __toString(): string;
}
