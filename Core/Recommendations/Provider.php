<?php

namespace Minds\Core\Recommendations;

use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Recommendations\Injectors\BoostSuggestionInjector;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind('Recommendations\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Recommendations\Controller', function ($di) {
            return new Controller();
        });
        $this->di->bind(BoostSuggestionInjector::class, function ($di): BoostSuggestionInjector {
            return new BoostSuggestionInjector();
        });
    }
}
