<?php

namespace Minds\Controllers\Cli;

use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Core\Media\Video\Transcoder\Delegates\DimensionsDelegate;

class VideoDimensions extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /** @var DimensionsDelegate */
    private $dimensions;

    /** @var Queue */
    private $queueClient;

    public function __construct($dimensions = null, $queueClient = null)
    {
        $this->dimensions = $dimensions ?? new DimensionsDelegate();
        $this->queueClient = $queueClient ?: Di::_()->get('Queue');
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    /**
     * Manually run reprocess job without using queue
     * Usage: php cli.php VideoDimensions exec --guid='1254745103390478346' --url='https://cdn-cinemr.minds.com/cinemr_dev/1254745103390478346/source'
     *
     * @return void
     */
    public function exec(): void
    {
        $guid = $this->getOpt('guid') ?? false;
        $url = $this->getOpt('url') ?? '';

        if (!$guid) {
            echo "[VideoDimensionsCli]: You must provide the video guid";
            return;
        }

        $dimensions = $this->dimensions->reprocess($guid, $url);

        if ($dimensions) {
            $width = $dimensions->getWidth();
            $height = $dimensions->getHeight();
            echo "[VideoDimensionsCli]: Success - manually reprocessed $guid to (h$height, w$width)";
            return;
        }
    }

    /**
     * Dispatch to VideoDimensions queue manually
     * Usage: php cli.php VideoDimensions dispatchToQueue --guid='1254745103390478346' --url='https://cdn-cinemr.minds.com/cinemr_dev/1254745103390478346/source'
     *
     * @return void
     */
    public function dispatchToQueue(): void
    {
        $guid = $this->getOpt('guid') ?? false;
        
        if (!$guid) {
            echo "You must provide the video guid";
            return;
        }
        
        $this->queueClient
            ->setQueue('VideoDimensions')
            ->send([
                'guid' => $guid,
                'url' => $this->getOpt('url') ?? ''
            ]);
    }
}
