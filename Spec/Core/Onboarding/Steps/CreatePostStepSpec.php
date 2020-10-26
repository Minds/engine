<?php

namespace Spec\Minds\Core\Onboarding\Steps;

use Minds\Core\Onboarding\Steps\CreatePostStep;
use Minds\Entities\User;
use Minds\Core\Feeds\Elastic;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CreatePostStepSpec extends ObjectBehavior
{
    /** @var Elastic\Manager */
    protected $elasticFeedManager;

    public function let(Elastic\Manager $elasticFeedManager)
    {
        $this->beConstructedWith($elasticFeedManager);
        $this->elasticFeedManager = $elasticFeedManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CreatePostStep::class);
    }

    public function it_should_check_if_completed(User $user)
    {
        $user->getGuid()
            ->willReturn(123);

        $this->elasticFeedManager->getList([
            'type' => 'activity',
            'owner_guid' => 123,
            'algorithm' => 'latest',
            'period' => 'relevant',
            'limit' => 1,
        ])
            ->willReturn([
                'this should be an entity.. but i am in a rush'
            ]);

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }

    public function it_should_check_if_not_completed(User $user)
    {
        $user->getGuid()
            ->willReturn(123);

        $this->elasticFeedManager->getList([
            'type' => 'activity',
            'owner_guid' => 123,
            'algorithm' => 'latest',
            'period' => 'relevant',
            'limit' => 1,
        ])
            ->willReturn([]);

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }
}
