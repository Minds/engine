<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Interfaces;
use Minds\Core\Sockets\Events as SocketEvents;

/**
 * Interact with sockets firing metric change events.
 */
class MetricChangeSockets extends Cli\Controller implements Interfaces\CliControllerInterface
{
    // allowed metrics.
    private $allowedMetrics = [
        'thumbs:up:count',
        'thumbs:down:count'
    ];

    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $this->help();
    }

    /**
     * Debug function to fire different values for metrics out of sockets to the front-end.
     * WILL NOT change actual metric values - just simulate a change for clients.
     * With no value provided, a random value between 0 and 50 will be chosen.
     * Metric will default to `thumbs:up:count`.
     * @example
     * - php cli.php MetricChangeSockets emit --metric='thumbs:up:count' --entityGuid=''
     * - php cli.php MetricChangeSockets emit --metric='thumbs:up:count' --value=10 --entityGuid=''
     * @return void
     */
    public function emit()
    {
        $metric = $this->getOpt('metric') ?? 'thumbs:up:count';
        $value = $this->getOpt('value') ?? rand(1, 50);
        $entityGuid = $this->getOpt('entityGuid');

        if (!$entityGuid) {
            $this->out('You must supply an entity guid');
        }

        if (!in_array($metric, $this->allowedMetrics, true)) {
            $this->out('Unsupported metric');
        }

        $roomName = "entity:metrics:$entityGuid";
        (new SocketEvents())
            ->setRoom($roomName) // send it to this group.
            ->emit($roomName, json_encode([$metric => $value]));
    }
}
