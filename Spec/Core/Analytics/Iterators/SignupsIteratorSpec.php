<?php

namespace Spec\Minds\Core\Analytics\Iterators;

use Minds\Core\Analytics\Iterators\SignupsIterator;
use Minds\Core\Data\Call;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SignupsIteratorSpec extends ObjectBehavior
{
    /** @var Call */
    protected $db;
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(Call $db, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($db, $entitiesBuilder);
        $this->db = $db;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SignupsIterator::class);
    }

    public function it_should_get_the_users(User $user1, User $user2)
    {
        $this->db->getRow(Argument::containingString('analytics:signup:day'), ['limit' => 200, 'offset' => ''])
            ->shouldBeCalled()
            ->willReturn([
                '1234' => time(),
                '5678' => time()
            ]);
        $user1->isBanned()->willReturn(false);
        $user2->isBanned()->willReturn(false);
        $user1->get('guid')->willReturn('1234');
        $user2->get('guid')->willReturn('5678');

        $this->entitiesBuilder->get(['guids' => ['1234', '5678']])
            ->shouldBeCalled()
            ->willReturn([$user1, $user2]);

        $this->setPeriod(20);

        $this->rewind();
        $this->current()->shouldReturn($user1);

        $this->next();
        $this->current()->shouldReturn($user2);
    }
}
