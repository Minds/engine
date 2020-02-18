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
                'homepage-december-2019',
                'onboarding-december-2019',
                'register_pages-december-2019',
                'modal-pager',
                'blockchain_creditcard',
                'channel-filter-feeds',
                'suggested-users',
                'top-feeds-filter',
                'media-modal',
                'wire-multi-currency',
                'cdn-jwt',
                'post-scheduler',
                'navigation',
                'wallet-upgrade'
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
