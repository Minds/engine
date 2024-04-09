<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Di\Di;
use Minds\Interfaces;

class PostHog extends Cli\Controller implements Interfaces\CliControllerInterface
{
    protected PostHogService $service;

    public function __construct()
    {
        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');
        $this->service = Di::_()->get(PostHogService::class);
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $this->out('See help');
    }

    public function syncCache()
    {
        $this->service->getFeatureFlags(useCache: false);
    }
}
