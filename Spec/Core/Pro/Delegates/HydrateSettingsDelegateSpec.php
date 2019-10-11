<?php

namespace Spec\Minds\Core\Pro\Delegates;

use Exception;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Pro\Delegates\HydrateSettingsDelegate;
use Minds\Core\Pro\Settings;
use Minds\Entities\Activity;
use Minds\Entities\Object\Carousel;
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
        Carousel $carousel,
        Activity $activity1,
        Activity $activity2
    ) {
        $settings->getLogoGuid()
            ->shouldBeCalled()
            ->willReturn(7500);

        $this->config->get('cdn_url')
            ->shouldBeCalled()
            ->willReturn('http://phpspec.test/');

        $settings->setLogoImage('http://phpspec.test/fs/v1/thumbnail/7500/master')
            ->shouldBeCalled()
            ->willReturn($settings);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->entitiesBuilder->get([
            'subtype' => 'carousel',
            'owner_guid' => '1000'
        ])
            ->shouldBeCalled()
            ->willReturn([ $carousel ]);

        $carousel->get('guid')
            ->shouldBeCalled()
            ->willReturn(9500);

        $carousel->get('last_updated')
            ->shouldBeCalled()
            ->willReturn(9999999);

        $settings->setBackgroundImage('http://phpspec.test/fs/v1/banners/9500/fat/9999999')
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
}
