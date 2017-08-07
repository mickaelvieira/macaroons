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

namespace Macaroons\Serialization\V1;

use Macaroons\Serialization\V1\Packet\Size;

/**
 * Class Packet
 *
 * @package Macaroons
 */
final class Packet
{
    const MAX_LENGTH    = 16 ** 4;
    const HEADER_LENGTH = 4;

    /** @var string */
    private $key;

    /** @var string */
    private $data;

    /** @var Size */
    private $size;

    /**
     * Packet constructor.
     *
     * @param string|null $key
     * @param string|null $data
     */
    public function __construct(string $key, string $data)
    {
        $this->key  = $key;
        $this->data = $data;
        $this->size = new Size($key, $data);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $encoded
     *
     * @return Packet
     *
     * @throws \DomainException
     */
    public static function fromEncoded(string $encoded): Packet
    {
        $size  = Size::toDec(substr($encoded, 0, self::HEADER_LENGTH));
        $start = self::HEADER_LENGTH; // skip the hex size
        $end   = $size - 5;           // minus hex size and trailing break line

        $parts = explode(' ', substr($encoded, $start, $end));

        if (count($parts) < 2) {
            throw new \DomainException('A packet appears to broken near');
        }

        $key  = array_shift($parts);
        $data = implode(' ', $parts);

        return new static($key, $data);
    }

    /**
     * @return string
     */
    public function getHexSize(): string
    {
        return $this->size->hex();
    }

    /**
     * @return int
     */
    public function getDecSize(): int
    {
        return $this->size->dec();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->size->hex() . $this->key . ' ' . $this->data . "\n";
    }
}
