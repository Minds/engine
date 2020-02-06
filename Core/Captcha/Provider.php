<?php
/**
 * Minds Captcha Provider.
 */

namespace Minds\Core\Captcha;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Captcha\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => true]);
    }
}
