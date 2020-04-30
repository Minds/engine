<?php

/**
 * Minds Features Provider
 *
 * @author emi
 */

namespace Minds\Core\Features;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Features provider
 * @package Minds\Core\Features
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
                'dark-mode',
                'allow-comments-toggle',
                'permissions',
                'pro',
                'purchase-pro',
                'webtorrent',
                'top-feeds-by-age',
                'modal-pager',
                'blockchain_creditcard',
                'channel-filter-feeds',
                'suggested-users',
                'top-feeds-filter',
                'media-modal',
                'wire-multi-currency',
                'cdn-jwt',
                'post-scheduler',
                'activity-composer',
                'ckeditor5',
                'navigation',
                'wallet-upgrade',
                'activity-v2--single-page',
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
            ];
        });

        $this->di->bind('Features\Manager', function ($di) {
            return new Manager();
        }, [ 'useFactory' => true ]);

        $this->di->bind('Features\Canary', function ($di) {
            return new Canary();
        }, [ 'useFactory' => true ]);
    }
}
