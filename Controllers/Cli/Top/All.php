<?php

namespace Minds\Controllers\Cli\Top;

use Exception;
use Minds\Core\Feeds\Elastic\Sync;
use Minds\Core\Minds;
use Minds\Cli;
use Minds\Exceptions\CliException;
use Minds\Interfaces;

class All extends Cli\Controller implements Interfaces\CliControllerInterface
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
        $this->out('Syntax usage: cli top all sync_<type> --metric=? --from=? --to=?');
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
    public function sync_activity(): void
    {
        list($from, $to) = $this->getTimeRangeFromArgs();
        $this->syncBy('activity', null, $this->getOpt('metric'), $from, $to);
    }

    /**
     * @throws CliException
     */
    public function sync_images(): void
    {
        list($from, $to) = $this->getTimeRangeFromArgs();
        $this->syncBy('object', 'image', $this->getOpt('metric'), $from, $to);
    }

    /**
     * @throws CliException
     */
    public function sync_videos(): void
    {
        list($from, $to) = $this->getTimeRangeFromArgs();
        $this->syncBy('object', 'video', $this->getOpt('metric'), $from, $to);
    }

    /**
     * @throws CliException
     */
    public function sync_blogs(): void
    {
        list($from, $to) = $this->getTimeRangeFromArgs();
        $this->syncBy('object', 'blog', $this->getOpt('metric'), $from, $to);
    }

    /**
     * @throws CliException
     */
    public function sync_groups(): void
    {
        list($from, $to) = $this->getTimeRangeFromArgs();
        $this->syncBy('group', null, $this->getOpt('metric'), $from, $to);
    }

    /**
     * @throws CliException
     */
    public function sync_channels(): void
    {
        list($from, $to) = $this->getTimeRangeFromArgs();
        $this->syncBy('user', null, $this->getOpt('metric'), $from, $to);
    }

    /**
     * @return int[]
     * @throws CliException
     */
    protected function getTimeRangeFromArgs(): array
    {
        $to = $this->getOpt('to') ?: time();

        if ($this->getOpt('from') && $this->getOpt('secsAgo')) {
            throw new CliException('Cannot specify both `from` and `secsAgo`');
        } elseif (!$this->getOpt('from') && !$this->getOpt('secsAgo')) {
            throw new CliException('You should specify either `from` or `secsAgo`');
        }

        if ($this->getOpt('secsAgo')) {
            $from = time() - $this->getOpt('secsAgo');
        } else {
            $from = $this->getOpt('from');
        }

        return [$from, $to];
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
    protected function syncBy($type, $subtype, $metric, $from, $to): void
    {
        if (!$metric) {
            throw new CliException('Missing `metric`');
        }

        if (!$from || !is_numeric($from)) {
            throw new CliException('Missing or invalid `from` value');
        }

        if (!$to || !is_numeric($to)) {
            throw new CliException('Invalid `to` value');
        }

        if ($from > $to) {
            throw new CliException('`from` must be lesser than `to`');
        }

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $displayType = trim(implode(':', [$type, $subtype]), ':');

        $this->out(sprintf(
            "%s -> %s",
            date('r', $from),
            date('r', $to),
        ));
        $this->out("Syncing {$displayType} / {$metric}");

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
