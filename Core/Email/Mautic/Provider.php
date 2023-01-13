<?php
/**
 * Mautic Provider.
 */

namespace Minds\Core\Email\Mautic;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Manager::class, function ($di) {
            return new Manager();
        }, ['useFactory' => true]);

        $this->di->bind(Client::class, function ($di) {
            return new Client();
        }, ['useFactory' => true]);

        // Marketing Attributes

        $this->di->bind(MarketingAttributes\Manager::class, function ($di) {
            return new MarketingAttributes\Manager();
        }, ['useFactory' => true]);

        $this->di->bind(MarketingAttributes\Repository::class, function ($di) {
            return new MarketingAttributes\Repository();
        }, ['useFactory' => true]);
    }
}
