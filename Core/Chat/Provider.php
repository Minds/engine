<?php
namespace Minds\Core\Chat;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(Controllers\ChatController::class, function (Di $di): Controllers\ChatController {
            return new Controllers\ChatController();
        });
    }
}
