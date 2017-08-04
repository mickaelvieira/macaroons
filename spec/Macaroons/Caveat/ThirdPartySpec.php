<?php

namespace spec\Macaroons\Caveat;

use Macaroons\Caveat\ThirdParty;
use Macaroons\Caveat;
use PhpSpec\ObjectBehavior;

class ThirdPartySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedWith('caveat_id', 'verification_id', 'http://google.com');
        $this->shouldHaveType(ThirdParty::class);
        $this->shouldImplement(Caveat::class);
    }

    function it_can_be_created_without_an_url()
    {
        $this->beConstructedWith('caveat_id', 'verification_id');
        $this->getCaveatId()->shouldReturn('caveat_id');
        $this->getVerificationId()->shouldReturn('verification_id');
        $this->shouldNotHaveLocation();
    }

    function it_returns_the_caveat_id()
    {
        $this->beConstructedWith('caveat_id', 'verification_id', 'http://google.com');
        $this->getCaveatId()->shouldReturn('caveat_id');
    }

    function it_returns_the_verification_id()
    {
        $this->beConstructedWith('caveat_id', 'verification_id', 'http://google.com');
        $this->getVerificationId()->shouldReturn('verification_id');
    }

    function it_returns_the_third_party_location()
    {
        $this->beConstructedWith('caveat_id', 'verification_id', 'http://google.com');
        $this->getLocation()->shouldReturn('http://google.com');
        $this->shouldHaveLocation();
    }

    function it_can_be_converted_into_an_array()
    {
        $this->beConstructedWith('caveat_id', 'verification_id', 'http://google.com');
        $this->toArray()->shouldReturn([
            'cid' => 'caveat_id',
            'vid' => 'verification_id',
            'cl' => 'http://google.com',
        ]);
    }

    function it_creates_the_verification_id_through_the_factory_method()
    {
        $this->beConstructedThrough('create', ['caveat_id', 'secret', 'signature', 'http://google.com']);
        $this->getCaveatId()->shouldReturn('caveat_id');
        $this->getVerificationId()->shouldNotBeNull();
        $this->getLocation()->shouldReturn('http://google.com');
    }
}
