<?php

namespace Spec\Minds\Core\Recommendations;

use Exception;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Core\Recommendations\UserRecommendationsCluster;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UserRecommendationsClusterSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(UserRecommendationsCluster::class);
    }

    /**
     * @throws Exception
     */
    public function it_should_return_cluster_id_for_recommendations(
        UserHashtagsManager $userHashtagsManager
    ) {
        $mockUser = new User(Argument::any());

        $userHashtagsManager
            ->setUser($mockUser)
            ->shouldBeCalledOnce();

        $userHashtagsManager
            ->get(Argument::type("array"))
            ->shouldBeCalledOnce()
            ->willReturn([
                [
                    'value' => "minds",
                    'selected' => true
                ],
                [
                    'value' => "news",
                    'selected' => true
                ],
                [
                    'value' => "photography",
                    'selected' => true
                ],
                [
                    'value' => "technology",
                    'selected' => true
                ]
            ]);

        $this->beConstructedWith($userHashtagsManager);

        $this
            ->calculateUserRecommendationsClusterId($mockUser)
            ->shouldBe(12);
    }
}
