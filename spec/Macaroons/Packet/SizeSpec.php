<?php

namespace spec\Macaroons\Packet;

use Macaroons\Packet\Size;
use PhpSpec\ObjectBehavior;

class SizeSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('identifier', '1234567890987654321');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Size::class);
    }

    function it_returns_the_size_in_decimals()
    {
        $this->dec()->shouldBeEqualTo(35);
    }

    function it_returns_the_size_in_hexadecimals()
    {
        $this->hex()->shouldBeEqualTo('0023');
    }

    function it_converts_a_decimal_into_an_hexadecimal()
    {
        $this::toHex(35)->shouldReturn('0023');
    }

    function it_converts_an_hexadecimal_into_a_decimal()
    {
        $this::toDec('0023')->shouldReturn(35);
    }
}
