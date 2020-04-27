<?php

namespace Spec\Minds\Core\Features\Services;

use Minds\Core\Config as CoreConfig;
use Minds\Core\Features\Services\Config;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConfigSpec extends ObjectBehavior
{
    /** @var CoreConfig */
    protected $config;

    public function let(
        CoreConfig $config
    ) {
        $this->config = $config;
        $this->beConstructedWith($config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Config::class);
    }

    public function it_should_sync()
    {
        $this
            ->sync(30)
            ->shouldReturn(true);
    }

    public function it_should_fetch(
        User $user1,
        User $user2
    ) {
        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => 'admin',
                'feature4' => 'canary',
                'feature5' => 'plus',
                'feature6' => 'pro',
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
                'feature3' => false,
                'feature4' => false,
                'feature5' => false,
                'feature6' => false,
            ]);

        $user1->isCanary()
            ->willReturn(true);

        $user1->isAdmin()
            ->willReturn(true);

        $user1->isPlus()
            ->willReturn(false);

        $user1->isPro()
            ->willReturn(false);

        $this
            ->setUser($user1)
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

        $user2->isCanary()
            ->willReturn(false);

        $user2->isAdmin()
            ->willReturn(false);

        $user2->isPlus()
            ->willReturn(true);

        $user2->isPro()
            ->willReturn(true);

        $this
            ->setUser($user2)
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
                'feature3' => false,
                'feature4' => false,
                'feature5' => true,
                'feature6' => true,
            ]);
    }
}
