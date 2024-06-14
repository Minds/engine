<?php
namespace Minds\Core\Queue;

use Minds\Core\Di\Di;

/**
 * Messaging queue
 */

class Client
{
    /**
     * Build the client
     * @param string $client
     * @throws \Exception
     */
    public static function build($client = ''): LegacyClient
    {
        return Di::_()->get('Queue');
    }
}
