<?php

namespace spec\Macaroons;

use PhpSpec\ObjectBehavior;

use function Macaroons\Crypto\hmac;
use function Macaroons\Crypto\gen_derived_key;

use Macaroons\Verifier;
use Macaroons\Macaroon;
use Macaroons\Exceptions\InvalidSignature;
use Macaroons\Exceptions\UnsatisfiedCaveat;

class MacaroonSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);
        $this->shouldHaveType(Macaroon::class);
    }

    function it_generates_the_macaroon_signature()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);
        $this->getSignature()->shouldReturn(hmac(gen_derived_key('secret key'), 'identifier'));
    }

    function it_does_not_have_any_cavets_by_default()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);
        $this->shouldNotHaveCaveats();
    }

    function it_returns_a_new_macaroon_with_the_new_first_party_caveat()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);
        $copy = $this->withFirstPartyCaveat('id');
        $this->shouldNotHaveCaveats();
        $copy->shouldHaveCaveats();
    }

    function it_returns_a_new_macaroon_with_the_new_third_party_caveat()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);
        $copy = $this->withThirdPartyCaveat('root_key', 'caveat_id', 'http://google.com');
        $this->shouldNotHaveCaveats();
        $copy->shouldHaveCaveats();
    }

    function it_returns_a_new_macaroon_with_the_new_signature()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this->withSignature('123');

        $copy->getSignature()->shouldNotReturn($this->getSignature());
    }

    function it_can_be_converted_into_a_string()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);
        $copy = $this
            ->withFirstPartyCaveat('user = 123456')
            ->withThirdPartyCaveat('root_key', 'user_auth', 'https://auth.google.com');

        $copy->__toString()->shouldNotBeNull();
    }

    function it_binds_a_discharge_macaroon_to_the_root_macaroon()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);
        $macaroon = $this->withThirdPartyCaveat('third_party_secret_key', 'caveat_id', 'http://auth.google.com');
        $discharge = Macaroon::create(
            'third_party_secret_key',
            'caveat_id',
            'https://auth.google.com'
        );

        $bound = $macaroon->bind($discharge);

        $bound->getSignature()->shouldNotBeEqualTo($discharge->getSignature());
    }

    function it_verifies_a_macaroon_first_party_caveat_with_exact_match()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'root secret key']);
        $macaroon = $this
            ->withFirstPartyCaveat('id = 123');

        $verifier = (new Verifier())
            ->satisfyExact('id = 123');

        $macaroon->verify('root secret key', $verifier)->shouldReturn(true);
    }

    function it_does_not_verify_a_macaroon_with_unsatisfied_first_party_caveat()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this
            ->withFirstPartyCaveat('id = 123')
            ->withFirstPartyCaveat('ip = 127.0.0.1');

        $verifier = (new Verifier())
            ->satisfyExact('id = 111')
            ->satisfyExact('ip = 127.0.0.1')
            ->satisfyGeneral(function ($value) {
                return $value === 'ip = 127.0.0.1';
            });

        $copy->shouldThrow(new UnsatisfiedCaveat('Caveat \'id = 123\' is not satisfied'))
            ->during('verify', ['secret key', $verifier]);
    }

    function it_verifies_a_macaroon_first_party_caveat_with_a_general_match_as_a_string()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this->withFirstPartyCaveat('id = 123');

        $verifier = (new Verifier())
            ->satisfyGeneral(function ($value) {
                return $value === 'id = 123';
            });

        $copy->verify('secret key', $verifier)->shouldReturn(true);
    }

    function it_verifies_a_macaroon_first_party_caveat_with_general_match_as_a_function()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this->withFirstPartyCaveat('id = 123');

        $verifier = (new Verifier())
            ->satisfyGeneral(function ($value) {
                return $value === 'id = 123';
            });

        $copy->verify('secret key', $verifier)->shouldReturn(true);
    }

    function it_verifies_a_macaroon_first_party_caveat_with_general_match_as_an_invokable_class()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this
            ->withFirstPartyCaveat('id = 123');

        $verifier = (new Verifier())
            ->satisfyGeneral(new class {
                public function __invoke($value)
                {
                    return $value === 'id = 123';
                }
            });

        $copy->verify('secret key', $verifier)->shouldReturn(true);
    }

    function it_verifies_a_third_party_caveat()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this
            ->withThirdPartyCaveat('third_party_secret_key', 'caveat_id', 'https://auth.google.com');

        $discharge = Macaroon::create(
            'https://auth.google.com',
            'caveat_id',
            'third_party_secret_key'
        );

        $discharge = $copy->bind($discharge);

        $verifier = (new Verifier())
            ->withDischargeMacaroon($discharge->getWrappedObject());

        $copy->verify('secret key', $verifier)->shouldReturn(true);
    }

    function it_verifies_a_third_party_caveat_which_has_a_first_party_caveat()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this
            ->withFirstPartyCaveat('ip = 127.0.0.1')
            ->withThirdPartyCaveat('third_party_secret_key', 'caveat_id', 'https://auth.google.com');

        $discharge = Macaroon::create('https://auth.google.com', 'caveat_id', 'third_party_secret_key')
            ->withFirstPartyCaveat('id = 123');

        $discharge = $copy->bind($discharge);

        $verifier = (new Verifier())
            ->satisfyExact('id = 123')         // satisfied by third party
            ->satisfyExact('ip = 127.0.0.1')   // satisfied by first party
            ->withDischargeMacaroon($discharge->getWrappedObject());

        $copy->verify('secret key', $verifier)->shouldReturn(true);
    }

    function it_verifies_a_third_party_caveat_which_has_a_third_party_caveat()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        // server root
        $copy = $this
            ->withThirdPartyCaveat('third_party1_secret_key', 'caveat_id_1', 'https://auth1.google.com');

        // server 1
        // discharge macaroon for third party 1
        $discharge1 = Macaroon::create('https://auth1.google.com', 'caveat_id_1', 'third_party1_secret_key')
            ->withFirstPartyCaveat('id = 123')
            ->withThirdPartyCaveat('third_party2_secret_key', 'caveat_id_2', 'https://auth2.google.com');

        $discharge1 = $copy->bind($discharge1);

        // server 2
        // discharge macaroon for third party 2
        $discharge2 = Macaroon::create('https://auth2.google.com', 'caveat_id_2', 'third_party2_secret_key')
            ->withFirstPartyCaveat('account = 123456');

        $discharge2 = $discharge1->bind($discharge2);

        $verifier = (new Verifier())
            ->satisfyExact('id = 123')            // satisfied by third party 1
            ->satisfyExact('account = 123456')    // satisfied by third party 2
            ->withDischargeMacaroons([
                $discharge1->getWrappedObject(),
                $discharge2->getWrappedObject()
            ]);

        $copy->verify('secret key', $verifier)->shouldReturn(true);
    }

    function it_does_not_verify_a_third_party_caveat_with_a_discharge_macaroon_with_an_invalid_key()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this
            ->withFirstPartyCaveat('ip = 127.0.0.1')
            ->withThirdPartyCaveat('third_party_secret_key', 'caveat_id', 'https://auth.google.com');

        $discharge = Macaroon::create('https://auth.google.com', 'caveat_id', 'discharge with an invalid secret key')
            ->withFirstPartyCaveat('id = 123');

        $discharge = $copy->bind($discharge);

        $verifier = (new Verifier())
            ->satisfyExact('id = 123')
            ->satisfyExact('ip = 127.0.0.1')
            ->withDischargeMacaroon($discharge->getWrappedObject());

        $copy->shouldThrow(new InvalidSignature('The discharge macaroon with id \'caveat_id\' signature is not valid'))
            ->during('verify', ['secret key', $verifier]);
    }

    function it_does_not_verify_the_macaroon_when_a_third_party_caveat_is_not_satisfied()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this
            ->withFirstPartyCaveat('ip = 127.0.0.1')
            ->withThirdPartyCaveat('third_party_secret_key', 'caveat_id', 'https://auth.google.com');

        $discharge = Macaroon::create('https://auth.google.com', 'caveat_id', 'third_party_secret_key')
            ->withFirstPartyCaveat('account = 123456')
            ->withFirstPartyCaveat('id = 123');

        $discharge = $copy->bind($discharge);

        $verifier = (new Verifier())
            ->satisfyExact('id = 123')
            ->satisfyExact('ip = 127.0.0.1')
            ->withDischargeMacaroon($discharge->getWrappedObject());

        $copy->shouldThrow(new UnsatisfiedCaveat('Caveat \'account = 123456\' is not satisfied'))
            ->during('verify', ['secret key', $verifier]);
    }

    function it_does_not_verify_a_macaroon_when_the_discharge_discharge_macaroon_is_missing()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this
            ->withThirdPartyCaveat('third_party_secret_key', 'caveat_id', 'https://auth.google.com');

        $copy->shouldThrow(new UnsatisfiedCaveat('Caveat \'caveat_id\' is not satisfied'))
            ->during('verify', ['secret key', new Verifier()]);
    }

    function it_verifies_a_macaroon_with_a_correct_signature()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $this->verify('secret key', new Verifier())->shouldReturn(true);
    }

    function it_does_not_verify_a_macaroon_without_caveats_and_with_an_incorrect_signature()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $this->shouldThrow(new InvalidSignature('The macaroon signature is not valid'))
            ->during('verify', ['different secret key', new Verifier()]);
    }

    function it_does_not_verify_a_macaroon_with_first_party_caveats_and_an_incorrect_signature()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this
            ->withFirstPartyCaveat('id = 123');

        $copy->shouldThrow(new UnsatisfiedCaveat('Caveat \'id = 123\' is not satisfied'))
            ->during('verify', ['different secret key', new Verifier()]);
    }

    function it_does_not_verify_a_macaroon_with_third_party_caveats_and_an_incorrect_signature()
    {
        $this->beConstructedThrough('create', ['https://google.com', 'identifier', 'secret key']);

        $copy = $this
            ->withThirdPartyCaveat('third_party_secret_key', 'caveat_id', 'https://auth.google.com');

        $copy->shouldThrow(new UnsatisfiedCaveat('Caveat \'caveat_id\' is not satisfied'))
            ->during('verify', ['different secret key', new Verifier()]);
    }

    function it_can_be_serialized()
    {
        $this->beConstructedThrough('create', ['https://target-service.co', '12345', '123']);

        $this->serialize()->shouldReturn(
            'MDAyN2xvY2F0aW9uIGh0dHBzOi8vdGFyZ2V0LXNlcnZpY2UuY28KMDAxNWlkZW50aWZpZXIgMTIzNDUKMDAyZnNpZ25hdHVyZSCa_Iibary2DNKgvTEycX378-oLUOyTFmP2UeJxaD_RzQo'
        );
    }

    function it_can_be_serialized_and_deserialized()
    {
        $this->beConstructedThrough('create', ['https://target-service.co', '12345', '123']);

        $deserialize = $this::deserialize($this->serialize());

        $deserialize->getIdentifier()->shouldReturn($this->getIdentifier());
        $deserialize->getLocation()->shouldReturn($this->getLocation());
        $deserialize->getSignature()->shouldReturn($this->getSignature());
        $deserialize->shouldNotHaveCaveats();
    }
}
