<?php
declare(strict_types=1);

namespace Minds\Core\Comments\GraphQL;

use Minds\Core\Comments\GraphQL\Controllers\PinnedCommentsController;
use Minds\Core\Di;
use Minds\Core\EntitiesBuilder;

class Provider extends Di\Provider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind(PinnedCommentsController::class, function ($di) {
            return new PinnedCommentsController(
                $di->get('Comments\Manager'),
                $di->get(EntitiesBuilder::class),
                $di->get('Logger')
            );
        });
    }
}
