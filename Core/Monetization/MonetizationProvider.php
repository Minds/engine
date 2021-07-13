<?php
/**
 * Minds Monetization Provider
 */

namespace Minds\Core\Monetization;

use Minds\Core\Di\Provider;

class MonetizationProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Monetization\Admin', function ($di) {
            return new Admin();
        }, [ 'useFactory' => true ]);

        $this->di->bind('Monetization\Manager', function ($di) {
            return new Manager();
        }, [ 'useFactory' => true ]);

        $this->di->bind('Monetization\Merchants', function ($di) {
            return new Merchants();
        }, [ 'useFactory' => true ]);

        $this->di->bind('Monetization\Users', function ($di) {
            return new Users();
        }, [ 'useFactory' => true ]);

        $this->di->bind('Monetization\ServiceCache', function ($di) {
            return new ServiceCache();
        }, [ 'useFactory' => true ]);

        /* Services */
    }
}
