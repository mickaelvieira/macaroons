<?php

namespace spec\Macaroons\Caveat;

use Macaroons\Caveat;
use Macaroons\Caveat\FirstParty;
use PhpSpec\ObjectBehavior;

class FirstPartySpec extends ObjectBehavior
{
    function it_is_initializable_with_a_predicate()
    {
        $this->beConstructedWith('domain = http://google.com');
        $this->shouldHaveType(FirstParty::class);
        $this->shouldImplement(Caveat::class);
    }

    function it_may_have_a_location()
    {
        $this->beConstructedWith('domain = http://google.com', 'http://google.com');
        $this->shouldHaveLocation();
        $this->getLocation()->shouldReturn('http://google.com');
    }

    function it_returns_the_predicate()
    {
        $this->beConstructedWith('domain = http://google.com');
        $this->shouldNotHaveLocation();
        $this->getPredicate()->shouldReturn('domain = http://google.com');
    }

    function it_returns_the_predicate_as_the_caveat_id()
    {
        $this->beConstructedWith('domain = http://google.com');
        $this->getCaveatId()->shouldReturn('domain = http://google.com');
    }

    function it_return_the_verification_id_which_is_always_equal_to_zero()
    {
        $this->beConstructedWith('user = 1234');
        $this->getVerificationId()->shouldReturn('0');
    }

    function it_can_be_converted_into_an_array()
    {
        $this->beConstructedWith('domain = http://google.com');
        $this->toArray()->shouldReturn([
            'cid' => 'domain = http://google.com'
        ]);
    }
}
