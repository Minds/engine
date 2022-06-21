<?php

namespace Spec\Minds\Core\Feeds\User;

use Minds\Common\Repository\Response;
use Minds\Core\Feeds\User\Manager;
use PhpSpec\ObjectBehavior;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Entities\User;

class ManagerSpec extends ObjectBehavior
{
    /** @var ElasticManager */
    protected $elasticManager;

    public function let(
        ElasticManager $elasticManager
    ) {
        $this->elasticManager = $elasticManager;
        $this->beConstructedWith($elasticManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_instance_after_setting_user(User $user)
    {
        $this->setUser($user)
            ->shouldBe($this);
    }

    public function it_should_see_if_user_has_made_posts(
        User $user,
        Response $response
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $response->count()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->elasticManager->getList([
            'container_guid' => 123,
            'limit' => 1,
            'algorithm' => 'latest',
            'period' => '1y',
            'type' => 'activity'
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->setUser($user);

        $this->hasMadePosts()
            ->shouldBe(true);
    }

    public function it_should_see_if_user_has_NOT_made_posts(
        User $user,
        Response $response
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $response->count()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->elasticManager->getList([
            'container_guid' => 123,
            'limit' => 1,
            'algorithm' => 'latest',
            'period' => '1y',
            'type' => 'activity'
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->setUser($user);

        $this->hasMadePosts()
            ->shouldBe(false);
    }
}
