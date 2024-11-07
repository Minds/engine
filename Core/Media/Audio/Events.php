<?php
namespace Minds\Core\Media\Audio;

use Minds\Common\Urn;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\Event;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;

class Events
{
    private AudioService $audioService;
    private EntitiesBuilder $entitiesBuilder;

    public function __construct(
        private readonly EventsDispatcher $eventsDispatcher,
        private readonly Config $config,
    ) {
    }

    public function register()
    {
        /**
         * When 'urn:audio:{guid}' is called, return from our AudioService
         */
        $this->eventsDispatcher->register('urn:resolve', 'all', function (Event $event) {
            /** @var Urn */
            $urn = $event->getParameters()['urn'];
        
            if ($urn->getNid() !== 'audio') {
                return;
            }

            /** @var AudioService */
            $audioService = Di::_()->get(AudioService::class);
            $event->setResponse($audioService->getByGuid($urn->getNss()));
        });

        /**
         * Provide information about the audio when activity post is exported
         */
        $this->eventsDispatcher->register('export:extender', 'activity', function (Event $event) {
            $params = $event->getParameters();
            /** @var Activity */
            $entity = $params['entity'];
            $export = $event->response() ?: [];

            if ($entity->getCustomType() === 'audio') {

                $attachment = $entity->attachments[0] ?? null;

                if ($attachment) {
                    // Get the audio entity
                    $audioEntity = $this->getAudioService()->getByGuid($attachment['guid']);

                    if ($audioEntity) {
                        $export['custom_data']['thumbnail_src'] = $this->config->get('site_url') . 'fs/v3/media/audio/' . $audioEntity->guid . '/thumbnail';
                        $export['custom_data']['src'] = $this->config->get('site_url') . 'fs/v3/media/audio/' . $audioEntity->guid . '/download';
                        $export['custom_data']['duration_secs'] = $audioEntity->durationSecs;
                        $event->setResponse($export);
                    }
 
                }
            }
        });

        /**
         * Tap into the security to check if the accessId (the activity post), is readable
         */
        $this->eventsDispatcher->register('acl:read', 'audio', function (Event $event) {
            $params = $event->getParameters();
            /** @var AudioEntity */
            $entity = $params['entity'];

            $parentEntity = $this->getEntitiesBuilder()->single($entity->getAccessId());
            $user = $params['user'];

            if ($parentEntity) {
                $canRead = ACL::_()->read($parentEntity, $user);
                $event->setResponse($canRead);
            }
        });
    }

    private function getAudioService(): AudioService
    {
        return $this->audioService ??= Di::_()->get(AudioService::class);
    }

    private function getEntitiesBuilder(): EntitiesBuilder
    {
        return $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
    }
}
