<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateConfigDelegate;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class UpdateConfigDelegateSpec extends ObjectBehavior
{
    private Collaborator $multiTenantConfigManagerMock;

    public function let(MultiTenantConfigManager $multiTenantConfigManagerMock)
    {
        $this->multiTenantConfigManagerMock = $multiTenantConfigManagerMock;
        $this->beConstructedWith($multiTenantConfigManagerMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UpdateConfigDelegate::class);
    }

    public function it_should_update()
    {
        $siteName = 'Example Site';
        $colorScheme = MultiTenantColorScheme::DARK;
        $primaryColor = '#FF0000';
        $description = 'Example description';

        $this->multiTenantConfigManagerMock->upsertConfigs(
            siteName: $siteName,
            colorScheme: $colorScheme,
            primaryColor: $primaryColor,
            federationDisabled: null,
            replyEmail: null,
            nsfwEnabled: null,
            boostEnabled: null,
            customHomePageEnabled: null,
            customHomePageDescription: $description,
            walledGardenEnabled: null,
            digestEmailEnabled: null,
            welcomeEmailEnabled: null,
            loggedInLandingPageIdWeb: null,
            loggedInLandingPageIdMobile: null,
            isNonProfit: null,
            lastCacheTimestamp: null
        )->shouldBeCalled();

        $this->onUpdate($siteName, $colorScheme, $primaryColor, $description);
    }

    public function it_should_update_config_with_null_values()
    {
        $this->multiTenantConfigManagerMock->upsertConfigs(
            siteName: null,
            colorScheme: null,
            primaryColor: null,
            federationDisabled: null,
            replyEmail: null,
            nsfwEnabled: null,
            boostEnabled: null,
            customHomePageEnabled: null,
            customHomePageDescription: null,
            walledGardenEnabled: null,
            digestEmailEnabled: null,
            welcomeEmailEnabled: null,
            loggedInLandingPageIdWeb: null,
            loggedInLandingPageIdMobile: null,
            isNonProfit: null,
            lastCacheTimestamp: null
        )->shouldBeCalled();

        $this->onUpdate();
    }
}
