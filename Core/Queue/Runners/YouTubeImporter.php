<?php

namespace Minds\Core\Queue\Runners;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Queue\Interfaces;

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
                $video = unserialize($d['video']);

                echo "[YouTubeImporter] Received a YouTube download request from {$video->getOwner()->username} ({$video->getOwner()->guid})\n";

                /** @var Core\Media\YouTubeImporter\Manager $manager */
                $manager = Di::_()->get('Media\YouTubeImporter\Manager');

                Core\Security\ACL::$ignore = true;
                $manager->onQueue($video);
                Core\Security\ACL::$ignore = false;
            }, ['max_messages' => 1]);
    }
}
