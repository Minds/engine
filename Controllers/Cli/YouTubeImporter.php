<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Media\YouTubeImporter\YTSubscription;
use Minds\Core\Media\YouTubeImporter\YTVideo;
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

    public function renewSubscriptions()
    {
        $ytSubscription = new YTSubscription();
        $entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $db = Di::_()->get('Database\Cassandra\Indexes');
        $rows = $db->getRow("yt_channel:connected-users");

        foreach ($rows as $userGuid => $channelId) {
            $user = $entitiesBuilder->single($userGuid);

            $ytChannel = $user->getYouTubeChannels()[0];

            if ($ytChannel['auto_import']) {
                $ytSubscription->renewLease($channelId);
                $this->out("$user->guid renewed lease");
            } else {
                $this->out("$user->guid has disable auto import");
            }
        }
    }

    public function patchIndex()
    {
        $file = fopen("ids.csv", "r");

        while (($data = fgetcsv($file)) !== false) {
            if ($data[0]) {
                $db = Di::_()->get('Database\Cassandra\Indexes');
                $row = $db->getRow("yt_channel:user:{$data[0]}");
                $userGuid = $row[0];
                $db->insert("yt_channel:connected-users", [ $userGuid => $data[0] ]);

                $this->out("$userGuid: {$data[0]}");
            }
        }
    }

    public function exec()
    {
    }
}
