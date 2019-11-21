<?php
/**
 * Provider
 * @author edgebal
 */

namespace Minds\Core\SSO;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('SSO', function () {
            return new Manager();
        });
    }
}
