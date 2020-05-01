<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Core\Di\Di;
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

    public function exec()
    {
        $this->out("[Cli/YouTubeImporter] Checking for videos to download");

        /** @var Core\Media\YouTubeImporter\Manager $manager */
        $manager = Di::_()->get('Media\YouTubeImporter\Manager');

        // get videos

        $videos = $manager->getVideos([
            'status' => 'queued',
            'limit' => 1000
        ]);

        $this->out("[Cli/YouTubeImporter] Found {$videos->count()} videos");

        // gather their owner guids

        $ownerGuids = [];

        foreach ($videos as $video) {
            if (!in_array($video->getOwnerGUID(), $ownerGuids, true)) {
                $ownerGuids[] = $video->getOwnerGUID();
            }
        }

        // check which owners are eligible for importing a YouTube a video today
        $ownerGuids = $manager->getOwnersEligibility($ownerGuids);

        // for all eligible owners, transcode their videos, keeping count so we don't surpass the threshold
        foreach ($videos as $video) {
            if (array_key_exists($video->getOwnerGUID(), $ownerGuids)
                && $ownerGuids[$video->getOwnerGUID()] < $manager->getThreshold()) {
                $this->out("[Cli/YouTubeDownloader] Sending video to transcode (guid: {$video->guid}");

                // transcode
                $manager->queue($video);

                // add 1 to the count of imported videos so we don't surpass the daily threshold
                $ownerGuids[$video->getOwnerGUID()] += 1;
            }
        }
    }
}
