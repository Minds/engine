<?php

namespace Spec\Minds\Core\Rewards\Contributions;

use Minds\Core\Util\BigNumber;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Rewards\Contributions\Repository;
use Minds\Core\Rewards\Contributions\Contribution;
use Minds\Core\Rewards\Contributions\Sums;
use Minds\Core\Analytics\Manager;
use Minds\Entities\User;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Rewards\Contributions\Manager');
    }

    public function it_should_sync_users_rewards_from_their_analytics(Repository $repository, Manager $analytics)
    {
        $this->beConstructedWith($analytics, $repository);

        $user = new User();
        $user->guid = 123;
        $this->setUser($user);
        

        $analytics->setUser(Argument::any())->shouldBeCalled()->willReturn($analytics);
        $analytics->setFrom(strtotime('-7 days') * 1000)->shouldBeCalled()->willReturn($analytics);
        $analytics->setTo(time() * 1000)->shouldBeCalled()->willReturn($analytics);
        $analytics->setInterval('day')->shouldBeCalled()->willReturn($analytics);
        $analytics->setOnlyPlus(false)->willReturn($analytics);

        $dayAgo = (strtotime('-1 day') * 1000);
        $twoDaysAgo = (strtotime('-2 days') * 1000);
        $threeDaysAgo = (strtotime('-3 days') * 1000);

        $analytics->getCounts()->shouldBeCalled()->willReturn([
            $dayAgo => [
                'votes' => 24,
                'downvotes' => 10,
            ],
            $twoDaysAgo => [
                'votes' => 40,
                'downvotes' => 20,
            ],
            $threeDaysAgo => [
                'votes' => 2,
                'downvotes' => 1,
            ]
        ]);

        $contributions = [
            (new Contribution)
                ->setMetric('votes')
                ->setUser($user)
                ->setTimestamp($dayAgo)
                ->setScore(24)
                ->setAmount(24),
            (new Contribution)
                ->setMetric('downvotes')
                ->setUser($user)
                ->setTimestamp($dayAgo)
                ->setScore(-10)
                ->setAmount(10),

            (new Contribution)
                ->setMetric('votes')
                ->setUser($user)
                ->setTimestamp($twoDaysAgo)
                ->setScore(40)
                ->setAmount(40),
            (new Contribution)
                ->setMetric('downvotes')
                ->setUser($user)
                ->setTimestamp($twoDaysAgo)
                ->setScore(-20)
                ->setAmount(20),
            
            (new Contribution)
                ->setMetric('votes')
                ->setUser($user)
                ->setTimestamp($threeDaysAgo)
                ->setScore(2)
                ->setAmount(2),
            (new Contribution)
                ->setMetric('downvotes')
                ->setUser($user)
                ->setTimestamp($threeDaysAgo)
                ->setScore(-1)
                ->setAmount(1),
        ];

        $repository->add($contributions)->shouldBeCalled();

        $this->sync();
    }

    public function it_should_return_the_value_rewards_to_issue(Sums $sums)
    {
        $this->beConstructedWith(null, null, $sums);

        $sums->setTimestamp(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($sums);
    
        $sums->setUser(Argument::any())
            ->shouldBeCalled()
            ->willReturn($sums);

        $sums->getScore()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->getRewardsAmount()->shouldReturn("15707963267949000");
    }
}
