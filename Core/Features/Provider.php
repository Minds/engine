<?php

/**
 * Minds Features Provider.
 *
 * @author emi
 */

namespace Minds\Core\Features;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Features provider.
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Features\Keys', function () {
            return [
                'psr7-router',
                'es-feeds',
                'helpdesk',
                'top-feeds',
                'cassandra-notifications',
                'allow-comments-toggle',
                'permissions',
                'pro',
                'purchase-pro',
                'webtorrent',
                'top-feeds-by-age',
                'modal-pager',
                'blockchain_creditcard',
                'suggested-users',
                'top-feeds-filter',
                'media-modal',
                'wire-multi-currency',
                'cdn-jwt',
                'post-scheduler',
                'ckeditor5',
                'navigation',
                'wallet-upgrade',
                'activity-v2--feeds',
                'activity-v2--boosts',
                'settings',
                'channels',
                'ux-2020',
                'code-highlight',
                'pay',
                'channels',
                'onboarding-reminder',
                'boost-rotator',
                'yt-importer',
                'yt-importer-transfer-all',
                'settings-referrals',
                'channels-shop',
                'topv2-algo',
                'localization-2020',
                'suggestions',
                'paywall-2020',
                'plus-2020',
                'nav-plus-2020',
                'support-tiers',
                'language-prompts',
                'discovery-carousel',
                'permaweb',
                'subscriber-conversations',
                'activity-modal',
                'channel-grid',
                'onboarding-october-2020',
                'unique-onchain',
                'liquidity-spot',
                'earn-modal',
                'buy-tokens-uniswap-transak',
                'guest-mode',
                'wallet-v3',
            ];
        });

        $this->di->bind('Features\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => true]);

        $this->di->bind('Features\Canary', function ($di) {
            return new Canary();
        }, ['useFactory' => true]);
    }
}
