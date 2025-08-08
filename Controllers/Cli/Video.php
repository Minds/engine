<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Media\Video\CloudflareStreams\Services\PruneVideosService;
use Minds\Interfaces;

class Video extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(
        private ?PruneVideosService $service = null,
    ) {
        Di::_()->get(Config::class)->set('min_log_level', 'INFO');
        $this->service = Di::_()->get(PruneVideosService::class);
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    public function exec()
    {
    }

    public function prune()
    {
        $this->service->process();
    }
}
