<?php
namespace Minds\Core\Channels\Groups;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Channel Groups Provider
 * @package Minds\Core\Channels\Groups
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Channels\Groups\Controller', function () {
            return new Controller();
        });

        $this->di->bind('Channels\Groups\Manager', function () {
            return new Manager();
        });
    }
}
