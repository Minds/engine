<?php

namespace Spec\Minds\Core\Blockchain\Transactions;

use Minds\Core\Data\Cassandra\Scroll;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ScrollRepositorySpec extends ObjectBehavior
{
    /** @var Scroll */
    private $scroll;

    public function let(
        Scroll $scroll
    ) {
        $this->scroll = $scroll;
        $this->beConstructedWith($scroll);
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Blockchain\Transactions\ScrollRepository');
    }
        
    public function it_should_get_distinct_offchain_user_guids()
    {
        $this->scroll->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(yield null);

        $this->getDistinctOffchainUserGuids();
    }
}
