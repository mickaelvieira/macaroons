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

namespace spec\Macaroons\Serialization\V1;

use Macaroons\Caveat;
use Macaroons\Macaroon;
use Macaroons\Serialization\V1\Serializer;
use Macaroons\Verifier;
use PhpSpec\ObjectBehavior;

class SerializerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Serializer::class);
    }

    function it_serializes_a_macaroon_with_no_caveats()
    {
        $macaroon = Macaroon::create('https://target-service.co', '12345', '123');

        $this->serialize($macaroon)->shouldReturn(
            'MDAyN2xvY2F0aW9uIGh0dHBzOi8vdGFyZ2V0LXNlcnZpY2UuY28KMDAxNWlkZW50aWZpZXIgMTIzNDUKMDAyZnNpZ25hdHVyZSCa_Iibary2DNKgvTEycX378-oLUOyTFmP2UeJxaD_RzQo'
        );
    }

    function it_triggers_an_exception_when_a_macaroon_is_malformed()
    {
        $malformed = 'MDAyNWxvdGlvbiBodHRwczovL3RhcmdldC1zZXJ2aWNlLmNvCjAwMTVpZGVudGlmaWVyIDEyMzQ1CjAwMmZzaWduYXR1cmUgmvyIm2q8tgzSoL0xMnF9-_PqC1DskxZj9lHicWg_0c0K';

        $this->shouldThrow(new \DomainException('Unknown packet key \'lotion\''))->during('deserialize', [$malformed]);
    }

    function it_serializes_and_deserializes_a_macaroon_with_no_caveats()
    {
        $macaroon = Macaroon::create('https://target-service.co', '12345', '123');

        $deserialize = $this->deserialize($this->serialize($macaroon));

        $deserialize->getIdentifier()->shouldReturn($macaroon->getIdentifier());
        $deserialize->getLocation()->shouldReturn($macaroon->getLocation());
        $deserialize->getSignature()->shouldReturn($macaroon->getSignature());
        $deserialize->shouldNotHaveCaveats();
    }

    function it_serializes_and_deserializes_an_macaroon_with_caveats()
    {
        $macaroon = Macaroon::create('https://target-service.co', '12345', '123');
        $macaroon = $macaroon
            ->withFirstPartyCaveat('user = 123456')
            ->withThirdPartyCaveat('987', 'user_auth', 'https://target-service.co');

        $deserialize = $this->deserialize($this->serialize($macaroon));

        $deserialize->getIdentifier()->shouldReturn($macaroon->getIdentifier());
        $deserialize->getLocation()->shouldReturn($macaroon->getLocation());
        $deserialize->getSignature()->shouldReturn($macaroon->getSignature());
        $deserialize->shouldHaveCaveats();
    }

    function it_serializes_and_deserializes_an_macaroon_with_caveats_and_it_can_be_verified()
    {
        $macaroon = Macaroon::create('https://target-service.co', '12345', '123');
        $macaroon = $macaroon
            ->withFirstPartyCaveat('user = 123456')
            ->withThirdPartyCaveat('987', 'user_auth', 'https://auth.target-service.co');

        $discharge = Macaroon::create('https://auth.target-service.co', 'user_auth', '987');
        $discharge = $discharge->withFirstPartyCaveat('account = 987654');
        $discharge = $macaroon->bind($discharge);

        $macaroon2 = $this->deserialize($this->serialize($macaroon));
        $discharge2 = $this->deserialize($this->serialize($discharge));

        $verifier = (new Verifier())
            ->satisfyExact('user = 123456')
            ->satisfyExact('account = 987654')
            ->withDischargeMacaroon($discharge2->getWrappedObject());

        $macaroon2->verify('123', $verifier)->shouldReturn(true);
    }

    function it_deserializes_an_macaroon_with_no_caveats()
    {
        $ser = 'MDAyN2xvY2F0aW9uIGh0dHBzOi8vdGFyZ2V0LXNlcnZpY2UuY28KMDAxNWlkZW50aWZpZXIgMTIzNDUKMDAyZnNpZ25hdHVyZSBY9k79AD4QC8CL7gaxktTlFKf6RhYV3aK6nGWbDGmleQo';

        $macaroon = $this->deserialize($ser);
        $macaroon->getLocation()->shouldReturn('https://target-service.co');
        $macaroon->getIdentifier()->shouldReturn('12345');
    }

    function it_deserializes_an_macaroon_with_caveats()
    {
        $ser = 'MDAyN2xvY2F0aW9uIGh0dHBzOi8vdGFyZ2V0LXNlcnZpY2UuY28KMDAxNWlkZW50aWZpZXIgMTIzNDUKMDAxNmNpZCB1c2VyID0gMTIzNDU2CjAwMTJjaWQgdXNlcl9hdXRoCjAwNTF2aWQgR9WEm5fSA_BFBD_GlQJCM5ERPKfiJM4XlbDxX2FmpQYq1T_rYenWAtXxbq_tfly9zCEtsr9v--c1M0-AZBlVRObbo82HtdDKCjAwMjFjbCBodHRwczovL3RhcmdldC1zZXJ2aWNlLmNvCjAwMmZzaWduYXR1cmUgFWZP3jmuaisMUZPit9KWOP1y1SrXu5YPFsIk_CmcX-sK';

        $macaroon = $this->deserialize($ser);

        $macaroon->getIdentifier()->shouldReturn('12345');
        $macaroon->getLocation()->shouldReturn('https://target-service.co');
        $macaroon->shouldHaveCaveats();
    }

    function it_deserializes_a_token_without_caveats_from_a_rust_library()
    {
        $deserialize = $this->deserialize('MDAyMWxvY2F0aW9uIGh0dHA6Ly9leGFtcGxlLm9yZy8KMDAxNWlkZW50aWZpZXIga2V5aWQKMDAyZnNpZ25hdHVyZSB83ueSURxbxvUoSFgF3-myTnheKOKpkwH51xHGCeOO9wo');

        $bytes = [124, 222, 231, 146, 81, 28, 91, 198, 245, 40, 72, 88, 5,
            223, 233, 178, 78, 120, 94, 40, 226, 169, 147, 1, 249, 215,
            17, 198, 9, 227, 142, 247];


        $sig = vsprintf(str_repeat('%c', count($bytes)), $bytes);

        $deserialize->getLocation()->shouldReturn('http://example.org/');
        $deserialize->getIdentifier()->shouldReturn('keyid');
        $deserialize->getSignature()->shouldReturn($sig);
        $deserialize->shouldNotHaveCaveats();
    }

    function it_deserializes_a_token_with_a_first_party_caveats_from_a_different_library()
    {
        $deserialize = $this->deserialize('MDAyMWxvY2F0aW9uIGh0dHA6Ly9leGFtcGxlLm9yZy8KMDAxNWlkZW50aWZpZXIga2V5aWQKMDAxZGNpZCBhY2NvdW50ID0gMzczNTkyODU1OQowMDJmc2lnbmF0dXJlIPVIB_bcbt-Ivw9zBrOCJWKjYlM9v3M5umF2XaS9JZ2HCg');

        $bytes = [245, 72, 7, 246, 220, 110, 223, 136, 191, 15, 115, 6, 179, 130, 37, 98, 163,
            98, 83, 61, 191, 115, 57, 186, 97, 118, 93, 164, 189, 37, 157, 135];

        $sig = vsprintf(str_repeat('%c', count($bytes)), $bytes);

        $deserialize->getLocation()->shouldReturn('http://example.org/');
        $deserialize->getIdentifier()->shouldReturn('keyid');
        $deserialize->getSignature()->shouldReturn($sig);
        $deserialize->shouldHaveCaveats();
    }

    function it_deserializes_a_token_with_2_first_party_caveats_from_a_different_library()
    {
        $deserialize = $this->deserialize('MDAyMWxvY2F0aW9uIGh0dHA6Ly9leGFtcGxlLm9yZy8KMDAxNWlkZW50aWZpZXIga2V5aWQKMDAxZGNpZCBhY2NvdW50ID0gMzczNTkyODU1OQowMDE1Y2lkIHVzZXIgPSBhbGljZQowMDJmc2lnbmF0dXJlIEvpZ80eoMaya69qSpTumwWxWIbaC6hejEKpPI0OEl78Cg');
        $bytes = [75, 233, 103, 205, 30, 160, 198, 178, 107, 175, 106, 74, 148, 238, 155,
            5, 177, 88, 134, 218, 11, 168, 94, 140, 66, 169, 60, 141, 14, 18, 94, 252];

        $sig = vsprintf(str_repeat('%c', count($bytes)), $bytes);

        $deserialize->getLocation()->shouldReturn('http://example.org/');
        $deserialize->getIdentifier()->shouldReturn('keyid');
        $deserialize->getSignature()->shouldReturn($sig);
        $deserialize->shouldHaveCaveats();

        foreach ($deserialize as $k => $caveat) {
            if ($k === 0) {
                $caveat->getPredicate()->shouldReturn('account = 3735928559');
            } else {
                $caveat->getPredicate()->shouldReturn('user = alice');
            }

            $caveat->shouldHaveType(Caveat::class);
        }
    }
}
