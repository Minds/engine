<?php
/**
 * Minds Referrals Provider
 */

namespace Minds\Core\Referrals;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(Controller::class, function(Di $di): Controller {
            return new Controller();
        });

        $this->di->bind('Referrals\Manager', function ($di) {
            return new Manager();
        }, [ 'useFactory'=>false ]);

        $this->di->bind(ReferralCookie::class, function ($di) {
            return new ReferralCookie();
        }, [ 'useFactory'=>true ]);
    }
}
