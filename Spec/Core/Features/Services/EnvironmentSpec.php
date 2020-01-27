<?php

namespace Spec\Minds\Core\Features\Services;

use Minds\Core\Features\Services\Environment;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EnvironmentSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Environment::class);
    }

    public function it_should_fetch(
        User $user1,
        User $user2
    ) {
        $global = [
            'MINDS_FEATURE_FEATURE1' => '1',
            'MINDS_FEATURE_FEATURE2' => '0',
            'MINDS_FEATURE_FEATURE3' => 'admin',
            'MINDS_FEATURE_FEATURE4' => 'canary',
            'MINDS_FEATURE_FEATURE5' => 'plus',
            'MINDS_FEATURE_FEATURE_6' => 'pro',
            'MINDS_FEATURE_UNUSED_FEATURE' => '1',
        ];

        $this
            ->setGlobal($global)
            ->fetch([
                'feature1',
                'feature2',
                'feature3',
                'feature4',
                'feature5',
                'feature-6',
            ])
            ->shouldReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => false,
                'feature4' => false,
                'feature5' => false,
                'feature-6' => false,
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
            ->setGlobal($global)
            ->fetch([
                'feature1',
                'feature2',
                'feature3',
                'feature4',
                'feature5',
                'feature-6',
            ])
            ->shouldReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => true,
                'feature4' => true,
                'feature5' => false,
                'feature-6' => false,
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
            ->setGlobal($global)
            ->fetch([
                'feature1',
                'feature2',
                'feature3',
                'feature4',
                'feature5',
                'feature-6',
            ])
            ->shouldReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => false,
                'feature4' => false,
                'feature5' => true,
                'feature-6' => true,
            ]);
    }
}
