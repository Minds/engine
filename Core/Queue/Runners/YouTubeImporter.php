<?php

namespace Minds\Core\Queue\Runners;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Queue\Interfaces;
use Minds\Entities\Video;

class YouTubeImporter implements Interfaces\QueueRunner
{
    public function run()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $client = Core\Queue\Client::Build();
        $client->setQueue("YouTubeImporter")
            ->receive(function ($data) {
                $d = $data->getData();
                /** @var Video $video */
                $videoGuid = $d['video_guid'];

                Core\Security\ACL::$ignore = true;

                $video = Di::_()->get(EntitiesBuilder::class)->single($videoGuid);

                if (!$video) {
                    error_log("Video $videoGuid not found");
                    return;
                }

                $owner = Di::_()->get(EntitiesBuilder::class)->single($video->getOwnerGuid());

                echo "[YouTubeImporter] Received a YouTube download request from {$owner->username} ({$owner->guid}) {$video->getGuid()}\n";

                /** @var Core\Media\YouTubeImporter\Manager $manager */
                $manager = Di::_()->get('Media\YouTubeImporter\Manager');

                $manager->onQueue($video);

                Core\Security\ACL::$ignore = false;
            }, ['max_messages' => 1]);
    }
}
