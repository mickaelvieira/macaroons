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

namespace Macaroons\Serialization;

use Macaroons\Macaroon;

/**
 * Interface Serializer
 *
 * @package Macaroons
 */
interface Serializer
{
    /**
     * @param Macaroon $macaroon
     *
     * @return string
     */
    public function serialize(Macaroon $macaroon): string;

    /**
     * @param string $data
     *
     * @return Macaroon
     *
     * @throws \DomainException
     * @throws \DomainException
     */
    public function deserialize(string $data): Macaroon;
}
