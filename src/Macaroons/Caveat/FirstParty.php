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

use function Macaroons\Crypto\hmac;

/**
 * Class FirstParty
 * @package Macaroons\Caveat
 */
final class FirstParty extends Caveat
{
    const VERIFICATION_ID = '0';

    /** @var string */
    private $predicate;

    /**
     * FirstParty constructor.
     *
     * @param string      $predicate
     * @param string|null $location
     */
    public function __construct(string $predicate, string $location = null)
    {
        $this->predicate      = $predicate;
        $this->verificationId = self::VERIFICATION_ID;
        $this->location       = $location;
    }

    /**
     * @return string
     */
    public function getPredicate(): string
    {
        return $this->predicate;
    }

    /**
     * {@inheritdoc}
     */
    public function getCaveatId(): string
    {
        return $this->predicate;
    }

    /**
     * {@inheritdoc}
     */
    public function sign(string $secret): string
    {
        return hmac($secret, $this->predicate);
    }

    /**
     * {@inheritdoc}
     */
    public function verify(Verifier $verifier, Macaroon $root): Verifier
    {
        // Does the predicate match any verifier check?
        if (!$verifier->verifyPredicate($this->getPredicate())) {
            throw new Exceptions\UnsatisfiedCaveat(sprintf('Caveat \'%s\' is not satisfied', $this->getCaveatId()));
        }

        // Pass on the signature to the next caveat
        return $verifier->withSignature($this->sign($verifier->getSignature()));
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'cid' => $this->predicate
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return sprintf(
            "cid %s\n%s",
            $this->predicate,
            $this->hasLocation() ? sprintf("cl %s\n", $this->getLocation()) : ''
        );
    }
}
