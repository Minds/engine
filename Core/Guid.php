<?php
namespace Minds\Core;

use Minds\Core\Log\Log;
use Minds\Traits\Logger;

/**
 * GUID Builder (ZQM servers + fallbacks)
 * @todo Avoid static and use DI
 */
class Guid
{
    public static $socket;

    /**
     * Generates a GUID
     * @return mixed
     */
    public static function build()
    {
        $guid = null;
        //use ZMQ id generator if we can
        if (class_exists('\ZMQContext')) {
            if (!self::$socket) {
                self::$socket = self::connect();
            }
            try {
                self::$socket->send('GEN');
                $guid = self::$socket->recv();
            } catch (\Exception $e) {
                Log::critical('Could not connect to GUID server, conflicts possible', static::class);
            }
        }
        if (!$guid) {
            $g = new \GUID();
            $guid = $g->generate();
        }
        return $guid;
    }

    /**
     * Connects to GUID generation server
     * @return \ZMQSocket
     */
    public static function connect()
    {
        global $CONFIG;
        $port = 5599;

        $socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REQ);
        $socket->connect("tcp://{$CONFIG->zmq_server}:{$port}");
        $socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $socket->setSockOpt(\ZMQ::SOCKOPT_RCVTIMEO, 500);
        $socket->setSockOpt(\ZMQ::SOCKOPT_SNDTIMEO, 500);
        return $socket;
    }
}
