<?php
/**
 * Minds Queue Provider
 */

namespace Minds\Core\Queue;

use Minds\Core\Di\Provider;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

class QueueProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Queue', function (Di $di): LegacyClient {
            return new LegacyClient(
                config: $di->get(Config::class),
                // topic: new LegacyQueueTopic(),
                logger: $di->get('Logger'),
            );
        }, [
            'useFactory' => true,
        ]);

    }
}
