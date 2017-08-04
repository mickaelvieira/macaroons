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

namespace Macaroons\Packet;

use Macaroons\Packet;

/**
 * Class Size
 *
 * @package Macaroons\Packet
 */
final class Size
{
    /** @var int */
    private $size;

    /**
     * Size constructor.
     *
     * @param string $key
     * @param string $data
     */
    public function __construct(string $key, string $data)
    {
        $this->size = Packet::HEADER_LENGTH + 2 + strlen($key) + strlen($data); // +2 = one space and one break line
    }

    /**
     * @param int $dec
     *
     * @return string
     */
    public static function toHex(int $dec): string
    {
        return str_pad(sprintf('%x', $dec), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param string $hex
     *
     * @return int|float
     */
    public static function toDec(string $hex)
    {
        return hexdec($hex);
    }

    /**
     * @return int
     */
    public function dec(): int
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function hex(): string
    {
        return self::toHex($this->size);
    }
}
