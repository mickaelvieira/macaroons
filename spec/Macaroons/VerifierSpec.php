<?php

namespace spec\Macaroons;

use Macaroons\Verifier;
use PhpSpec\ObjectBehavior;

class VerifierSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Verifier::class);
    }

    function it_can_have_an_exact_value_validator()
    {
        $copy = $this->satisfyExact('user = 1111');

        $this->shouldNotHavePredicates();
        $copy->shouldHavePredicates();
    }

    function it_can_have_a_general_value_validator()
    {
        $copy = $this->satisfyGeneral(function () {
            return true;
        });

        $this->shouldNotHaveCallbacks();
        $copy->shouldHaveCallbacks();
    }
}
