<?php

namespace Minds\Controllers\Cli;

use Minds\Core\Feeds\Elastic\Sync;
use Minds\Core\Minds;
use Minds\Cli;
use Minds\Exceptions\CliException;
use Minds\Interfaces;

class Top extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /** @var Sync */
    private $sync;

    public function __construct()
    {
        $minds = new Minds();
        $minds->start();

        $this->sync = new Sync();
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli top sync_<type> --metric=?');
    }

    public function exec()
    {
        $this->out('Syntax usage: cli top sync_<type> --metric=?');
    }

    public function sync_activity()
    {
        return $this->syncBy('activity', null, $this->getOpt('metric') ?? null);
    }

    public function sync_images()
    {
        return $this->syncBy('object', 'image', $this->getOpt('metric') ?? null);
    }

    public function sync_videos()
    {
        return $this->syncBy('object', 'video', $this->getOpt('metric') ?? null);
    }

    public function sync_blogs()
    {
        return $this->syncBy('object', 'blog', $this->getOpt('metric') ?? null);
    }

    public function sync_groups()
    {
        return $this->syncBy('group', null, $this->getOpt('metric') ?? null);
    }

    public function sync_channels()
    {
        return $this->syncBy('user', null, $this->getOpt('metric') ?? null);
    }

    protected function syncBy($type, $subtype, $metric)
    {
        if (!$metric) {
            throw new CliException('Missing --metric flag');
        }

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $displayType = trim(implode(':', [$type, $subtype]), ':');

        $this->out("Syncing {$displayType} -> {$metric}");

        $this->sync
            ->setType($type ?: '')
            ->setSubtype($subtype ?: '')
            ->setMetric($metric)
            ->setFrom(strtotime('-1 day') * 1000)
            ->setTo(time() * 1000)
            ->run();

        $this->out("\nCompleted syncing '{$displayType}'.");
    }
}
