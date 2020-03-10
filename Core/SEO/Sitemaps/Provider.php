<?php
/**
 * Provider
 *
 * @author Mark
 */

namespace Minds\Core\SEO\Sitemaps;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

/**
 * DI Provider bindings
 *
 * @package Minds\Core\Log
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Sitemaps\Manager', function ($di) {
            return new Manager();
        }, [ 'useFactory' => false ]);
    }
}
