<?php

namespace Minds\Core\Router\Hooks;

/**
 * Interface defining a shutdown handler manager.
 */
interface ShutdownHandlerManagerInterface
{
    /**
     * Registers all shutdown handlers.
     * @return void
     */
    public function registerAll(): void;
}
