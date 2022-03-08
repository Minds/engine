<?php
namespace Minds\Interfaces;

/**
 * Interface for shutdown handler.
 */
interface ShutdownHandlerInterface
{
    /**
     * Register shutdown handler.
     */
    public function register();
}
