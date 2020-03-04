<?php

namespace Spec\Minds\Core\Pro\Delegates;

use Exception;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Pro\Delegates\HydrateSettingsDelegate;
use Minds\Core\Pro\Settings;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class HydrateSettingsDelegateSpec extends ObjectBehavior
{
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Config */
    protected $config;

    public function let(
        EntitiesBuilder $entitiesBuilder,
        Config $config
    ) {
        $this->entitiesBuilder = $entitiesBuilder;
        $this->config = $config;

        $this->beConstructedWith($entitiesBuilder, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(HydrateSettingsDelegate::class);
    }

    public function it_should_hydrate_settings_on_get(
        User $user,
        Settings $settings,
        Activity $activity1,
        Activity $activity2
    ) {
        $this->config->get('cdn_url')
            ->shouldBeCalled()
            ->willReturn('http://phpspec.test/');

        $settings->hasCustomLogo()
            ->shouldBeCalled()
            ->willReturn(true);

        $settings->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $settings->getTimeUpdated()
            ->shouldBeCalled()
            ->willReturn(999999);

        $settings->setLogoImage('http://phpspec.test/fs/v1/pro/1000/logo/999999')
            ->shouldBeCalled()
            ->willReturn($settings);

        $settings->hasCustomBackground()
            ->shouldBeCalled()
            ->willReturn(true);

        $settings->setBackgroundImage('http://phpspec.test/fs/v1/pro/1000/background/999999')
            ->shouldBeCalled()
            ->willReturn($settings);

        $user->getPinnedPosts()
            ->shouldBeCalled()
            ->willReturn([5000, 5001]);

        $this->entitiesBuilder->get(['guids' => ['5000', '5001']])
            ->shouldBeCalled()
            ->willReturn([ $activity1, $activity2 ]);

        $activity1->get('time_created')
            ->shouldBeCalled()
            ->willReturn(10000010);

        $activity1->get('entity_guid')
            ->shouldBeCalled()
            ->willReturn(7400);

        $activity2->get('time_created')
            ->shouldBeCalled()
            ->willReturn(10000090);

        $activity2->get('guid')
            ->shouldBeCalled()
            ->willReturn(5001);

        $activity2->get('entity_guid')
            ->shouldBeCalled()
            ->willReturn(null);

        $settings->setFeaturedContent([5001, 7400])
            ->shouldBeCalled()
            ->willReturn($settings);

        $user->isProPublished()
                ->willReturn(false);

        $settings->setPublished(false)
            ->shouldBeCalled();

        $this
            ->shouldNotThrow(Exception::class)
            ->duringOnGet($user, $settings);
    }

    public function it_should_hydrate_settings_with_default_assets_on_get(
        User $user,
        Settings $settings,
        Activity $activity1,
        Activity $activity2
    ) {
        $settings->hasCustomLogo()
            ->shouldBeCalled()
            ->willReturn(false);

        $user->getIconURL('large')
            ->shouldBeCalled()
            ->willReturn('http://phpspec.test/fs/v1/avatar/1000');

        $settings->setLogoImage('http://phpspec.test/fs/v1/avatar/1000')
            ->shouldBeCalled()
            ->willReturn($settings);

        $settings->hasCustomBackground()
            ->shouldBeCalled()
            ->willReturn(false);

        $user->getPinnedPosts()
            ->shouldBeCalled()
            ->willReturn([5000, 5001]);

        $this->entitiesBuilder->get(['guids' => ['5000', '5001']])
            ->shouldBeCalled()
            ->willReturn([ $activity1, $activity2 ]);

        $activity1->get('time_created')
            ->shouldBeCalled()
            ->willReturn(10000010);

        $activity1->get('entity_guid')
            ->shouldBeCalled()
            ->willReturn(7400);

        $activity2->get('time_created')
            ->shouldBeCalled()
            ->willReturn(10000090);

        $activity2->get('guid')
            ->shouldBeCalled()
            ->willReturn(5001);

        $activity2->get('entity_guid')
            ->shouldBeCalled()
            ->willReturn(null);

        $settings->setFeaturedContent([5001, 7400])
            ->shouldBeCalled()
            ->willReturn($settings);

        $user->isProPublished()
                ->willReturn(false);

        $settings->setPublished(false)
            ->shouldBeCalled();

        $this
            ->shouldNotThrow(Exception::class)
            ->duringOnGet($user, $settings);
    }
}
