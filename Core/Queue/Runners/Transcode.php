<?php
namespace Minds\Core\Queue\Runners;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Queue\Interfaces;

class Transcode implements Interfaces\QueueRunner
{
    public function run()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $client = Core\Queue\Client::Build();
        $client->setQueue("Transcode")
            ->receive(function ($data) {
                $d = $data->getData();
                $transcode = unserialize($d['transcode']);

                echo "\n[{$transcode->getGuid()} ({{$transcode->getProfile()->getId()})] Started Transcoding";

                // ACL override needed to update the state of the video
                Core\Security\ACL::$ignore = true;

                /** @var Core\Media\Video\Transcoder\Manager $transcoderManager */
                $transcoderManager = Di::_()->get('Media\Video\Transcoder\Manager');
                $transcoderManager->transcode($transcode);

                // Return ACL state
                Core\Security\ACL::$ignore = false;

                echo "\n[{$transcode->getGuid()} ({{$transcode->getProfile()->getId()})] Completed Transcoding";
            }, [ 'max_messages' => 1 ]);
    }
}
