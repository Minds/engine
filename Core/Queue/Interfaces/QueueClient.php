<?php
namespace Minds\Core\Queue\Interfaces;

/**
 * Queue client interface
 */
interface QueueClient
{
    /**
     * @param string $name
     * @return QueueClient
     */
    public function setQueue($name = "default");

    /**
     * @param $message
     * @return mixed
     */
    public function send(array $message);

    /**
     * @param $callback
     * @return mixed
     */
    public function receive($callback);
};
