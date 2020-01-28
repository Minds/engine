<?php

namespace Spec\Minds\Core\Features\Services;

use Minds\Core\Config;
use Minds\Core\Features\Services\Unleash;
use Minds\Entities\User;
use Minds\UnleashClient\Entities\Context;
use Minds\UnleashClient\Unleash as UnleashClient;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UnleashSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var UnleashClient */
    protected $unleash;

    public function let(
        Config $config,
        UnleashClient $unleash
    ) {
        $this->config = $config;
        $this->unleash = $unleash;
        $this->beConstructedWith($config, $unleash);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Unleash::class);
    }


    public function it_should_fetch()
    {
        $this->unleash->setContext(Argument::that(function (Context $context) {
            return
                $context->getUserId() === null &&
                $context->getUserGroups() === ['anonymous']
                ;
        }))
            ->shouldBeCalled()
            ->willReturn($this->unleash);

        $this->unleash->export()
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => true,
                'feature4' => true,
                'feature5' => false,
                'feature6' => false,
                'unused-feature' => true,
            ]);

        $this
            ->fetch([
                'feature1',
                'feature2',
                'feature3',
                'feature4',
                'feature5',
                'feature6',
            ])
            ->shouldReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => true,
                'feature4' => true,
                'feature5' => false,
                'feature6' => false,
            ]);
    }

    public function it_should_fetch_with_user(
        User $user
    ) {
        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $user->isAdmin()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->isCanary()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->isPlus()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->isPro()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->unleash->setContext(Argument::that(function (Context $context) {
            return
                $context->getUserId() === '1000' &&
                $context->getUserGroups() === ['authenticated', 'admin', 'canary', 'pro', 'plus']
                ;
        }))
            ->shouldBeCalled()
            ->willReturn($this->unleash);

        $this->unleash->export()
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => true,
                'feature4' => true,
                'feature5' => false,
                'feature6' => false,
                'unused-feature' => true,
            ]);

        $this
            ->setUser($user)
            ->fetch([
                'feature1',
                'feature2',
                'feature3',
                'feature4',
                'feature5',
                'feature6',
            ])
            ->shouldReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => true,
                'feature4' => true,
                'feature5' => false,
                'feature6' => false,
            ]);
    }
}
