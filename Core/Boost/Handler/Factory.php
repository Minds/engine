<?php

namespace Minds\Core\Boost\Handler;

use Minds\Interfaces\BoostHandlerInterface;

/**
 * A factory providing handlers boosting items
 */
class Factory
{
    const HANDLER_CHANNEL = 'channel';
    const HANDLER_CONTENT = 'content';
    const HANDLER_NETWORK = 'network';
    const HANDLER_NEWSFEED = 'newsfeed';
    const HANDLER_PEER = 'peer';

    const HANDLERS = [
        self::HANDLER_CHANNEL => Channel::class,
        self::HANDLER_CONTENT => Content::class,
        self::HANDLER_NETWORK => Network::class,
        self::HANDLER_NEWSFEED => Newsfeed::class,
        self::HANDLER_PEER => Peer::class
    ];

    /**
     * @param string $handler
     * @return BoostHandlerInterface
     * @throws \Exception
     */
    public static function get(string $handler): BoostHandlerInterface
    {
        if (!isset(self::HANDLERS[$handler]) || !class_exists(self::HANDLERS[$handler])) {
            throw new \Exception("Handler not found");
        }

        $class = self::HANDLERS[$handler];
        return new $class;
    }
}
