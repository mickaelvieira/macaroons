<?php

namespace spec\Macaroons;

use Macaroons\Packet;
use PhpSpec\ObjectBehavior;

class PacketSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedWith('key', 'data');
        $this->shouldHaveType(Packet::class);
    }

    function it_can_be_converted_into_a_string()
    {
        $this->beConstructedWith('identifier', '1234567890987654321');
        $this->__toString()->shouldReturn("0023identifier 1234567890987654321\n");
    }

    function it_can_be_construct_through_an_encoded_string()
    {
        $this->beConstructedThrough('fromEncoded', ["0023identifier 1234567890987654321\nwhatever comes after this"]);

        $this->getData()->shouldReturn('1234567890987654321');
        $this->getKey()->shouldReturn('identifier');
        $this->getDecSize()->shouldReturn(35);
        $this->getHexSize()->shouldReturn('0023');
    }

    function it_can_be_construct_through_an_encoded_string_containing_spaces()
    {
        $this->beConstructedThrough('fromEncoded', ["0016cid user = 123456\nwhatever comes after this"]);

        $this->getData()->shouldReturn('user = 123456');
        $this->getKey()->shouldReturn('cid');
        $this->getDecSize()->shouldReturn(22);
        $this->getHexSize()->shouldReturn('0016');
    }

    function xit_triggers_an_exception_when_trying_to_decode_malformed_packet()
    {
        // $this->shouldThrow(new \DomainException())->during('fromEncoded', ["0016cid\nwhatever"]);
        // not testable at the moment but it will be possible with phpspec 4
        // https://github.com/phpspec/phpspec/commit/f86c400df5ea214432916e4e99552b9b01fbb5e6
    }
}
