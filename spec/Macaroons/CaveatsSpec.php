<?php

namespace spec\Macaroons;

use Macaroons\Caveat;
use Macaroons\Caveats;
use PhpSpec\ObjectBehavior;

class CaveatsSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Caveats::class);
        $this->shouldImplement(\Countable::class);
        $this->shouldImplement(\IteratorAggregate::class);
    }

    function it_should_be_empty_by_default()
    {
        $this->shouldBeEmpty();
        $this->shouldHaveCount(0);
        $this->count()->shouldReturn(0);
    }

    function it_can_be_initialized_with_a_set_of_caveats()
    {
        $caveats = [
            new Caveat\FirstParty('user = 12345678'),
            new Caveat\ThirdParty('root_key', 'caveat_id', 'http://google.com')
        ];

        $this->beConstructedWith($caveats);
        $this->shouldHaveCount(2);
    }

    function it_should_add_a_caveat_to_the_set()
    {
        $bag = $this->with(new Caveat\FirstParty('user = 12345678'));
        $this->shouldHaveCount(0);
        $bag->shouldHaveCount(1);
    }

    function it_should_remove_a_cavet_from_the_set()
    {
        $caveat = new Caveat\FirstParty('user = 12345678');
        $caveats = $this->with($caveat);
        $this->shouldHaveCount(0);
        $caveats->shouldHaveCount(1);

        $caveats = $caveats->without($caveat);
        $caveats->shouldHaveCount(0);
    }

    function it_should_not_blow_up_when_removing_an_unexisting_caveat_from_the_set()
    {
        $caveat1 = new Caveat\FirstParty('user = 12345678');
        $caveat2 = new Caveat\FirstParty('user = 12345678');
        $caveats = $this->with($caveat1);
        $this->shouldHaveCount(0);
        $caveats->shouldHaveCount(1);

        $caveats = $caveats->without($caveat2);
        $caveats->shouldHaveCount(1);
    }

    function it_returns_the_first_party_caveats()
    {
        $caveat1 = new Caveat\FirstParty('user = 12345678');
        $caveat2 = new Caveat\FirstParty('user = 12345678');
        $caveat3 = new Caveat\ThirdParty('root_key', 'caveat_id', 'http://google.com');
        $caveats = $this->with($caveat1)->with($caveat2)->with($caveat3);
        $caveats->findFirstParty()->shouldBeLike([$caveat1, $caveat2]);
    }

    function it_returns_the_third_party_caveats()
    {
        $caveat1 = new Caveat\FirstParty('user = 12345678');
        $caveat2 = new Caveat\FirstParty('user = 12345678');
        $caveat3 = new Caveat\ThirdParty('root_key', 'caveat_id', 'http://google.com');
        $caveats = $this->with($caveat1)->with($caveat2)->with($caveat3);
        $caveats->findThirdParty()->shouldBeLike([$caveat3]);
    }

    function it_is_clonable()
    {
        $caveat1 = new Caveat\FirstParty('user = 12345678');
        $caveat2 = new Caveat\FirstParty('user = 12345678');

        $copy = $this->with($caveat1)->with($caveat2);

        $copy->shouldHaveCount(2);

        $copy2 = clone $copy;

        $copy2->shouldHaveCount(2);
    }
}
