<?php
namespace Minds\Core\Rewards;

use Minds\Core\Di;
use Minds\Core\Rewards\Withdraw\Admin\Controller as AdminController;
use Minds\Core\Rewards\Withdraw\Admin\Manager as AdminManager;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Rewards\Contributions\Manager', function ($di) {
            return new Contributions\Manager();
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Rewards\Contributions\Repository', function ($di) {
            return new Contributions\Repository();
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Rewards\Contributions\DailyCollection', function ($di) {
            return new Contributions\DailyCollection();
        }, [ 'useFactory'=> false ]);

        $this->di->bind('Rewards\Withdraw\Manager', function ($di) {
            return new Withdraw\Manager();
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Rewards\Withdraw\Repository', function ($di) {
            return new Withdraw\Repository();
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Rewards\ReferralValidator', function ($di) {
            return new ReferralValidator();
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Rewards\JoinedValidator', function ($di) {
            return new JoinedValidator();
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Rewards\OfacBlacklist', function ($di) {
            return new OfacBlacklist();
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Rewards\Controller', function ($di) {
            return new Controller();
        }, [ 'useFactory' => false]);

        $this->di->bind('Rewards\Manager', function ($di) {
            return new Manager();
        }, [ 'useFactory' => false]);

        $this->di->bind('Rewards\Notify', function ($di) {
            return new Notify();
        }, [ 'useFactory' => false]);

        $this->di->bind('Rewards\Withdraw\Admin\Controller', function ($di) {
            return new AdminController();
        }, [ 'useFactory' => false]);

        $this->di->bind('Rewards\Withdraw\Admin\Manager', function ($di) {
            return new AdminManager();
        }, [ 'useFactory' => false]);

        $this->di->bind('Rewards\Eligibility\Manager', function ($di) {
            return new Eligibility\Manager();
        }, [ 'useFactory' => false]);
    }
}
