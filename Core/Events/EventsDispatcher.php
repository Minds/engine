<?php


namespace Minds\Core\Events;

class EventsDispatcher
{

    /**
     * Register a handler for an event.
     * @param string $event The event
     * @param string $namespace Namespace for this event (e.g. object type)
     * @param callable $handler a callable handler
     * @param string $priority Priority - lower numbers executed first.
     */
    public function register($event, $namespace, $handler, $priority = 500)
    {
        return Dispatcher::register($event, $namespace, $handler, $priority);
    }

    /**
     * Unregister a handler.
     * TODO: Handle unregister of closures.
     * @param string $namespace
     * @param string $event
     * @param callable $handler
     */
    public function unregister($namespace, $event, $handler)
    {
        Dispatcher::unregister($namespace, $event, $handler);
    }

    /**
     * Trigger the event.
     * @param string $event
     * @param string $namespace
     * @param mixed $params Parameters to pass to the callback
     * @param mixed $default_return Default return value, if not set by the handler.
     */
    public function trigger($event, $namespace, $params, $default_return = null)
    {
        return Dispatcher::trigger($event, $namespace, $params, $default_return);
    }
}
