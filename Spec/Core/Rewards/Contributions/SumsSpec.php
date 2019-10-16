<?php

namespace Spec\Minds\Core\Rewards\Contributions;

use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Data\Cassandra\Client;

class SumsSpec extends ObjectBehavior
{
    /** @var Client */
    protected $db;

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Rewards\Contributions\Sums');
    }

    public function let(Client $db)
    {
        $this->beConstructedWith($db);
        $this->db = $db;
    }

    public function it_sould_get_a_balance()
    {
        $this->db->request(Argument::any())->willReturn([
            ['amount' => 12]
        ]);

        $this->getAmount()->shouldReturn('12');
    }

    public function it_should_get_the_score_for_all_users()
    {
        $scoreData = [
            ['score' => 146]
        ];
        $scoreDecimalData = [
            ['score' => 23.65]
        ];

        $this->setTimestamp(1571220000000);
        $this->db->request(Argument::type(Custom::class))->willReturn($scoreData, $scoreDecimalData);
        $this->getScore()->shouldReturn(169.65);
    }

    public function it_should_get_the_score_for_a_user(User $user)
    {
        $scoreData = [
            ['score' => 146]
        ];
        $scoreDecimalData = [
            ['score' => 23.65]
        ];

        $this->setTimestamp(1571220000000);
        $this->setUser($user);
        $user->get('guid')->shouldBeCalled()->willReturn(1001);
        $this->db->request(Argument::type(Custom::class))->willReturn($scoreData, $scoreDecimalData);
        $this->getScore()->shouldReturn(169.65);
    }
}
