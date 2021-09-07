<?php
namespace Minds\Core\Helpdesk\Zendesk;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Zendesk Provider
 * @package Minds\Core\Helpdesk\Zendesk
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Helpdesk\Zendesk\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Helpdesk\Zendesk\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
