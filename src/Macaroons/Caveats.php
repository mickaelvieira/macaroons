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

use Macaroons\Caveat\FirstParty;
use Macaroons\Caveat\ThirdParty;

/**
 * Class Caveats
 * @package CollectionJson
 */
final class Caveats implements \Countable, \IteratorAggregate
{
    /**
     * @var Caveat[]
     */
    private $caveats;

    /**
     * Caveats constructor.
     *
     * @param array $caveats
     */
    public function __construct(array $caveats = [])
    {
        $this->caveats = $caveats;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->caveats);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->caveats);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->caveats);
    }

    /**
     * @param Caveat $caveat
     *
     * @return Caveats
     */
    public function with(Caveat $caveat): Caveats
    {
        $copy = clone $this;

        $copy->caveats[] = $caveat;

        return $copy;
    }

    /**
     * @param Caveat $item
     *
     * @return Caveats
     */
    public function without(Caveat $item): Caveats
    {
        $key = array_search($item, $this->caveats, true);

        if ($key === false) {
            return $this;
        }

        $copy = clone $this;

        unset($copy->caveats[$key]);

        return $copy;
    }

    /**
     * @return Caveat[]
     */
    public function findFirstParty(): array
    {
        return array_values(array_filter($this->caveats, function (Caveat $caveat) {
            return $caveat instanceof FirstParty;
        }));
    }

    /**
     * @return Caveat[]
     */
    public function findThirdParty(): array
    {
        return array_values(array_filter($this->caveats, function (Caveat $caveat) {
            return $caveat instanceof ThirdParty;
        }));
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $toString = function (Caveat $caveat) {
            return (string)$caveat;
        };

        return implode('', array_map($toString, $this->caveats));
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $clone = function (Caveat $caveat) {
            return clone $caveat;
        };

        $this->caveats = array_map($clone, $this->caveats);
    }
}
