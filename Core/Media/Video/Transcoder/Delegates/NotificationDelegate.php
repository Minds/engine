<?php
namespace Minds\Core\Media\Video\Transcoder\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Entities\Video;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\EntitiesBuilder;

class NotificationDelegate
{
    /** @var TranscodeStates */
    private $transcodeStates;

    /** @var EventsDispatcher */
    private $eventsDispatcher;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct($transcodeStates = null, $eventsDispatcher = null, $entitiesBuilder = null)
    {
        $this->transcodeStates = $transcodeStates ?? new TranscodeStates();
        $this->eventsDispatcher = $eventsDispatcher ?? Di::_()->get('EventsDispatcher');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Add a transcode to the queue
     * @param Transcode $transcode
     * @return void
     */
    public function onTranscodeCompleted(Transcode $transcode): void
    {
        $video = $this->entitiesBuilder->single($transcode->getGuid());
        if (!$video || !$video instanceof Video) {
            error_log("Video ({$transcode->getGuid()}not found");
            return; // TODO: Tell sentry?
        }

        $status = $this->transcodeStates->getStatus($video);

        if ($status === TranscodeStates::COMPLETED) {
            // $this->emitCompletedNotification($video);
        } elseif ($status === TranscodeStates::FAILED) {
            // $this->emitFailedNotification($video);
        }
    }

    /**
     * @var Video $video
     * @return void
     */
    private function emitCompletedNotification(Video $video): void
    {
        $this->eventsDispatcher->trigger('notification', 'transcoder', [
            'to'=> [ $video->getOwnerGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'transcode_completed',
            'entity' => $video,
        ]);
    }

    /**
     * @var Video $video
     * @return void
     */
    private function emitFailedNotification(Video $video): void
    {
        $this->eventsDispatcher->trigger('notification', 'transcoder', [
            'to'=> [ $video->getOwnerGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'transcode_failed',
            'entity' => $video,
        ]);
    }
}
