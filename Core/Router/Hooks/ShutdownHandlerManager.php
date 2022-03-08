<?php

namespace Minds\Core\Router\Hooks;

use Minds\Core\Analytics\Handlers\GrowthbookShutdownHandler;

/**
 * Manager for all shutdown handlers - these are functions that will run
 * after script execution.
 */
class ShutdownHandlerManager implements ShutdownHandlerManagerInterface
{
    // will run in order of registration.
    const handlers = [
        GrowthbookShutdownHandler::class
    ];

    /**
     * Register all shutdown handlers.
     * @return void
     */
    public function registerAll(): void
    {
        foreach (self::handlers as $handler) {
            (new $handler)->register();
        }
    }
}
