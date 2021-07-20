<?php
/**
 * Pool Factory
 *
 */

namespace Minds\Core\Data;

use phpcassa\Connection\ConnectionPool;

class Pool
{
    public static $pools = [];

    public static function build($keyspace, $servers = ['localhost'], $poolsize = 2, $retries = 2, $sendTimeout = 200, $receiveTimeout = 800)
    {
//        return  new ConnectionPool($keyspace, $servers, $poolsize, 2, $sendTimeout, $receiveTimeout);

        if (!isset(self::$pools[$keyspace])) {
            self::$pools[$keyspace] = new ConnectionPool($keyspace, $servers, $poolsize, $retries, $sendTimeout, $receiveTimeout);
        }

        return self::$pools[$keyspace];
    }
}
