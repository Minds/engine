<?php
namespace Minds\Core\Feeds\GraphQL;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(Controllers\NewsfeedController::class, function ($di) {
            return new Controllers\NewsfeedController();
        });
    }
}
