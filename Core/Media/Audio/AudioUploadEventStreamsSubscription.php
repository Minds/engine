<?php
/**
 * This subscription will build notifications from stream events
 * `php cli.php EventStreams --subscription=Core\\Media\\Audio\\AudioUploadEventStreamsSubscription`
 */
namespace Minds\Core\Media\Audio;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Audio\AudioEntity;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Media\Audio\Exceptions\BadRemoteAudioFileException;

class AudioUploadEventStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?AudioService $audioService = null,
        private ?Logger $logger = null,
    ) {
        $this->audioService ??= Di::_()->get(AudioService::class);
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'audio_upload';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new ActionEventsTopic();
    }

    /**
     * @return string
     */
    public function getTopicRegex(): string
    {
        return ActionEvent::ACTION_AUDIO_UPLOAD;
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            $this->logger->info('Skipping as not an action event');
            return false;
        }

        $this->logger->info('Action event type: ' . $event->getAction());

        $audioEntity = $event->getEntity();

        if (!$audioEntity instanceof AudioEntity) {
            return true; // We don't care, its not an audio entity
        }

        // Process the audio
        try {
            $success = $this->audioService->processAudio($audioEntity);
            if ($success) {
                $this->logger->info($audioEntity->guid . ' was processed');
            } else {
                $this->logger->info($audioEntity->guid . ' failed to process');
            }
        } catch (BadRemoteAudioFileException $e) {
            $this->logger->info($audioEntity->guid . ' failed to process', [
                'exception' => $e
            ]);
            return true; // Do not try again.
        }

        return $success;
    }
}
