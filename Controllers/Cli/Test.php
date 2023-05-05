<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core;
use Minds\Core\EventStreams\Topics\TestEventsTopic;
use Minds\Interfaces;

class Test extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
        define('__MINDS_INSTALLING__', true);
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $namespace = Core\Entities::buildNamespace([
            'type' => 'object',
            'subtype' => 'video',
            'network' => '732337264197111809'
        ]);

        $this->out($namespace);
    }

    public function pulsar_php_80_send_message(): void
    {
        $topic = new TestEventsTopic();
        $topic->send(null);
    }
}
