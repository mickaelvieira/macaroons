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

use Macaroons\Caveat;
use Macaroons\Packet;
use Macaroons\Macaroon;
use Macaroons\Serialization\Serializer as SerializerContract;

use function Macaroons\Crypto\base64_url_encode;
use function Macaroons\Crypto\base64_url_decode;

/**
 * Class Serializer
 *
 * @package Macaroons\Serialization\V1
 */
final class Serializer implements SerializerContract
{
    /**
     * {@inheritdoc}
     */
    public function serialize(Macaroon $macaroon): string
    {
        $packets = [];

        $packets[] = new Packet('location', $macaroon->getLocation());
        $packets[] = new Packet('identifier', $macaroon->getIdentifier());

        foreach ($macaroon as $caveat) {
            /** @var Caveat $caveat */
            $packets[] = new Packet('cid', $caveat->getCaveatId());

            if ($caveat instanceof Caveat\ThirdParty) {
                $packets[] = new Packet('vid', $caveat->getVerificationId());
            }

            if ($caveat->hasLocation()) {
                $packets[] = new Packet('cl', $caveat->getLocation());
            }
        }

        $packets[] = new Packet('signature', $macaroon->getSignature());

        $toString = function (Packet $packet) {
            return (string)$packet;
        };

        return base64_url_encode(implode('', array_map($toString, $packets)));
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize(string $data): Macaroon
    {
        $index      = 0;
        $stack      = [];
        $caveats    = [];
        $decoded    = base64_url_decode($data);
        $location   = null;
        $identifier = null;
        $signature  = null;

        while ($index < strlen($decoded)) {
            $packet = Packet::fromEncoded(substr($decoded, $index));

            switch ($packet->getKey()) {
                case 'location':
                    $location = $packet->getData();
                    break;
                case 'identifier':
                    $identifier = $packet->getData();
                    break;
                case 'signature':
                    $signature = $packet->getData();
                    break;
                case 'cid':
                    if (!empty($stack)) {
                        $caveats[] = $stack;
                        $stack = [];
                    }
                    $stack['cid'] = $packet->getData();
                    break;
                case 'vid':
                    $stack['vid'] = $packet->getData();
                    break;
                case 'cl':
                    $stack['cl'] = $packet->getData();
                    break;
                default:
                    throw new \DomainException(sprintf('Unknown packet key \'%s\'', $packet->getKey()));
            }

            $index += $packet->getDecSize();
        }

        if (!empty($stack)) {
            $caveats[] = $stack;
        }

        $toCaveat = function ($data) {
            return Caveat::fromArray($data);
        };

        return new Macaroon($location, $identifier, $signature, array_map($toCaveat, $caveats));
    }
}
