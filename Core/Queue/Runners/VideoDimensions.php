<?php

namespace Minds\Core\Queue\Runners;

use Minds\Core\Queue;
use Minds\Core\Queue\Interfaces;
use Minds\Core\Media\Video\Transcoder\Delegates\DimensionsDelegate;

/**
 * Queue to reprocess video dimensions.
 */
class VideoDimensions implements Interfaces\QueueRunner
{
    public function run()
    {
        $client = Queue\Client::Build();
        $client->setQueue("VideoDimensions")
            ->receive(function ($msg) {
                $data = $msg->getData();

                $guid = $data['guid'] ?? false;
                $url = $data['url'] ?? false;

                echo "Received a new video dimensions reprocessing request";

                $dimensions = new DimensionsDelegate();
                $dimensions->reprocess($guid, $url);
            });
    }
}
