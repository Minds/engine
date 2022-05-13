<?php

namespace Minds\Core\EventStreams;

/**
 *
 */
trait AcknowledgmentEventTrait
{
    /**
     * @var callable
     */
    private $forceAcknowledgeCallback;

    /**
     * @return mixed
     */
    public function forceAcknowledge(): mixed
    {
        return call_user_func($this->forceAcknowledgeCallback);
    }

    /**
     * @param callable $callback
     * @return void
     */
    public function onForceAcknowledge(callable $callback): void
    {
        $this->forceAcknowledgeCallback = $callback;
    }
}
