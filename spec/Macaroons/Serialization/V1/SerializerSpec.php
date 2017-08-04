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
}
