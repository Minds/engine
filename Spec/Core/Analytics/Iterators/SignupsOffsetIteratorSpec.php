<?php

namespace Spec\Minds\Core\Analytics\Iterators;

use Minds\Core\Analytics\Iterators\SignupsOffsetIterator;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;

class SignupsOffsetIteratorSpec extends ObjectBehavior
{
    /** @var Client */
    protected $db;
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(Client $db, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($db, $entitiesBuilder);
        $this->db = $db;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SignupsOffsetIterator::class);
    }

    public function it_should_get_the_users(User $user1, User $user2)
    {
        $this->db->request(Argument::that(function ($query) {
            $built = $query->build();
            return $built['string'] === "SELECT * from entities_by_time where key='user' and column1>?"
                && $built['values'] = [''];
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([
                ['column1' => '1234'],
                ['column1' => '5678']
            ], ''));

        $user1->isBanned()->willReturn(false);
        $user2->isBanned()->willReturn(false);
        $user1->get('guid')->willReturn('1234');
        $user2->get('guid')->willReturn('5678');
        $user1->get('time_created')->willReturn('5678');
        $user2->get('time_created')->willReturn('5678');

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
