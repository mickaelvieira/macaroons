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
 * Class Verifier
 *
 * @package Macaroons
 */
final class Verifier
{
    /** @var array */
    private $predicates = [];

    /** @var array */
    private $callbacks = [];

    /** @var array */
    private $discharges = [];

    /**
     * Carries the macaroon signature (.ie hmac chaining)
     * produced during the validation process
     *
     * @var string
     */
    private $signature;

    /**
     * @param string $signature
     *
     * @return Verifier
     */
    public function withSignature(string $signature): Verifier
    {
        $copy = clone $this;
        $copy->signature = $signature;

        return $copy;
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string $predicate
     *
     * @return Verifier
     */
    public function satisfyExact(string $predicate): Verifier
    {
        $copy = clone $this;
        $copy->predicates[] = $predicate;

        return $copy;
    }

    /**
     * @return bool
     */
    public function hasPredicates(): bool
    {
        return !empty($this->predicates);
    }

    /**
     * @param callable $callback
     *
     * @return Verifier
     */
    public function satisfyGeneral(callable $callback): Verifier
    {
        $copy = clone $this;
        $copy->callbacks[] = $callback;

        return $copy;
    }

    /**
     * @return bool
     */
    public function hasCallbacks(): bool
    {
        return !empty($this->callbacks);
    }

    /**
     * @param Macaroon $discharge
     *
     * @return Verifier
     */
    public function withDischargeMacaroon(Macaroon $discharge): Verifier
    {
        $copy = clone $this;
        $copy->discharges[] = $discharge;

        return $copy;
    }

    /**
     * @param Verifier[] $discharges
     *
     * @return Verifier
     */
    public function withDischargeMacaroons(array $discharges): Verifier
    {
        return array_reduce($discharges, function (Verifier $verifier, Macaroon $macaroon) {
            return $verifier->withDischargeMacaroon($macaroon);
        }, $this);
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function verifyPredicate(string $value): bool
    {
        $match = array_filter($this->predicates, function (string $predicate) use ($value) {
            return $value === $predicate;
        });

        if (!empty($match)) {
            return true;
        }

        $match = array_filter($this->callbacks, function (callable $callback) use ($value) {
            return $callback($value);
        });

        if (!empty($match)) {
            return true;
        }

        return false;
    }

    /**
     * @param Caveat $caveat
     *
     * @return null|Macaroon
     */
    public function retrieveDischargeMacaroonVerifyingThisCaveat(Caveat $caveat)
    {
        $search = array_filter($this->discharges, function (Macaroon $discharge) use ($caveat) {
            return (
                $discharge->getIdentifier() === $caveat->getCaveatId() &&
                (!$caveat->hasLocation() || $discharge->getLocation() === $caveat->getLocation())
            );
        });

        return current($search) ?: null;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $clone = function (Macaroon $discharge) {
            return clone $discharge;
        };

        $this->discharges = array_map($clone, $this->discharges);
    }
}
