<?php

namespace Spec\Minds\Core\Experiments;

use Minds\Core\Config\Config;
use Minds\Core\Experiments\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Growthbook;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Core\Experiments\Cookie\Manager as CookieManager;
use GuzzleHttp;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Data\cache\SharedCache;
use Minds\Entities\User;
use PhpSpec\Wrapper\Collaborator;

class ManagerSpec extends ObjectBehavior
{
    protected Collaborator $postHogServiceMock;

    public function let(
        PostHogService $postHogServiceMock,
    ) {
        $this->beConstructedWith(
            $postHogServiceMock
        );
        $this->postHogServiceMock = $postHogServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_true_if_feature_flag_is_on()
    {
        $user = new User();

        $this->postHogServiceMock->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->postHogServiceMock);

        $this->postHogServiceMock->getFeatureFlags()
            ->willReturn([
                'phpspec-test' => true
            ]);

        $this->setUser($user)->isOn('phpspec-test')
            ->shouldBe(true);
    }

    public function it_should_return_false_if_feature_flag_is_not_on()
    {
        $user = new User();

        $this->postHogServiceMock->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->postHogServiceMock);

        $this->postHogServiceMock->getFeatureFlags()
            ->willReturn([
                'phpspec-test' => false
            ]);

        $this->setUser($user)->isOn('phpspec-test')
            ->shouldBe(false);
    }

    public function it_should_return_false_if_feature_flag_is_not_configured()
    {
        $user = new User();

        $this->postHogServiceMock->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->postHogServiceMock);

        $this->postHogServiceMock->getFeatureFlags()
            ->willReturn([
            ]);

        $this->setUser($user)->isOn('phpspec-test')
            ->shouldBe(false);
    }

    public function it_should_return_true_if_feature_flag_variation_matches()
    {
        $user = new User();

        $this->postHogServiceMock->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->postHogServiceMock);

        $this->postHogServiceMock->getFeatureFlags()
            ->willReturn([
                'phpspec-test' => 'apples'
            ]);

        $this->setUser($user)->hasVariation('phpspec-test', 'apples')
            ->shouldBe(true);
    }

    public function it_should_return_false_if_feature_flag_variation_doesnt_match()
    {
        $user = new User();

        $this->postHogServiceMock->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->postHogServiceMock);

        $this->postHogServiceMock->getFeatureFlags()
            ->willReturn([
                'phpspec-test' => 'apples'
            ]);

        $this->setUser($user)->hasVariation('phpspec-test', 'oranges')
            ->shouldBe(false);
    }
}
