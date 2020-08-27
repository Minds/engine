<?php
namespace Minds\Core\I18n;

use Minds\Core\Di\Provider as DiProvider;

/**
 * i18n Provider
 * @package Minds\Core\I18n
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('I18n\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => true]);

        $this->di->bind('I18n\Translator', function ($di) {
            return new Translator();
        }, ['useFactory' => true]);
    }
}
