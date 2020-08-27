<?php
namespace Minds\Core\Wire\SupportTiers;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Wire Support Tiers DI Provider
 * @package Minds\Core\Wire\SupportTiers
 */
class Provider extends DiProvider
{
    /**
     * Registers all module bindings
     */
    public function register(): void
    {
        $this->di->bind('Wire\SupportTiers\Controller', function ($di) {
            return new Controller();
        });

        $this->di->bind('Wire\SupportTiers\Manager', function ($di) {
            return new Manager();
        });
    }
}
