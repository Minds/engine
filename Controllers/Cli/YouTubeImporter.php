<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Entities\Video;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Exceptions\ProvisionException;

class YouTubeImporter extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
        define('__MINDS_INSTALLING__', true);
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function import()
    {
        $channelId = $this->getOpt('channel_id');
        $videoId = $this->getOpt('video_id');

        $ytSubscription = new YTSubscription();

        $video = (new YTVideo())
            ->setVideoId($videoId)
            ->setChannelId($channelId);

        $ytSubscription->onNewVideo($video);
    }

    public function renewSubscription()
    {
        $channelId = $this->getOpt('channel_id');
        
        $ytSubscription = new YTSubscription();
        $success = $ytSubscription->renewLease($channelId);

        if ($success) {
            $this->out('Renewed');
        } else {
            $this->out('There was an error');
        }
    }

    public function exec()
    {
    }
}
