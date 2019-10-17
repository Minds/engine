<?php
/**
 * Minds Referrals Provider
 */
 
namespace Minds\Core\Referrals;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Referrals\Manager', function ($di) {
            return new Manager();
        }, [ 'useFactory'=>false ]);
        $this->di->bind('Referrals\Cookie', function ($di) {
            return new ReferralCookie();
        }, [ 'useFactory'=>true ]);
    }
}
