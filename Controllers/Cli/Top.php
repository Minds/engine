<?php

namespace Minds\Controllers\Cli;

use Exception;
use Minds\Core\Feeds\Elastic\Sync;
use Minds\Core\Minds;
use Minds\Cli;
use Minds\Exceptions\CliException;
use Minds\Interfaces;

class Top extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /** @var Sync */
    private $sync;

    /**
     * Top constructor.
     */
    public function __construct()
    {
        $minds = new Minds();
        $minds->start();

        $this->sync = new Sync();
    }

    /**
     * @param null $command
     * @return void
     */
    public function help($command = null)
    {
        $this->out('Syntax usage: cli top sync_<type> --metric=? --from=? --to=?');
    }

    /**
     * @return void
     */
    public function exec()
    {
        $this->help();
    }

    /**
     * @throws CliException
     */
    public function sync_activity()
    {
        return $this->syncBy('activity', null, $this->getOpt('metric'), $this->getOpt('from'), $this->getOpt('to'));
    }

    /**
     * @throws CliException
     */
    public function sync_images()
    {
        return $this->syncBy('object', 'image', $this->getOpt('metric'), $this->getOpt('from'), $this->getOpt('to'));
    }

    /**
     * @throws CliException
     */
    public function sync_videos()
    {
        return $this->syncBy('object', 'video', $this->getOpt('metric'), $this->getOpt('from'), $this->getOpt('to'));
    }

    /**
     * @throws CliException
     */
    public function sync_blogs()
    {
        return $this->syncBy('object', 'blog', $this->getOpt('metric'), $this->getOpt('from'), $this->getOpt('to'));
    }

    /**
     * @throws CliException
     */
    public function sync_groups()
    {
        return $this->syncBy('group', null, $this->getOpt('metric'), $this->getOpt('from'), $this->getOpt('to'));
    }

    /**
     * @throws CliException
     */
    public function sync_channels()
    {
        return $this->syncBy('user', null, $this->getOpt('metric'), $this->getOpt('from'), $this->getOpt('to'));
    }

    /**
     * @param $type
     * @param $subtype
     * @param $metric
     * @param $from
     * @param $to
     * @throws CliException
     * @throws Exception
     */
    protected function syncBy($type, $subtype, $metric, $from, $to)
    {
        if (!$metric) {
            throw new CliException('Missing --metric flag');
        }

        if (!$from || !is_numeric($from)) {
            throw new CliException('Missing --from flag');
        }

        if (!$to || !is_numeric($to)) {
            throw new CliException('Missing --to flag');
        }

        if ($from > $to) {
            throw new CliException('--from should be before --to');
        }

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $displayType = trim(implode(':', [$type, $subtype]), ':');

        $this->out("Syncing {$displayType} -> {$metric}");

        $this->sync
            ->setType($type ?: '')
            ->setSubtype($subtype ?: '')
            ->setMetric($metric)
            ->setFrom($from * 1000)
            ->setTo($to * 1000)
            ->run();

        $this->out("\nCompleted syncing '{$displayType}'.");
    }
}
