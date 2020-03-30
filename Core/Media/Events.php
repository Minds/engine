<?php
namespace Minds\Core\Media;

use Minds\Core;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Entities;

class Events
{
    public function register()
    {
        Dispatcher::register('entities:map', 'all', function ($event) {
            $params = $event->getParameters();

            if ($params['row']->type == 'object') {
                switch ($params['row']->subtype) {
                    case 'video':
                    case 'kaltura_video':
                        $event->setResponse(new Entities\Video($params['row']));
                        break;
                    
                    case 'audio':
                        $event->setResponse(new Entities\Audio($params['row']));
                        break;
                    
                    case 'image':
                        $event->setResponse(new Entities\Image($params['row']));
                        break;

                    case 'album':
                        $event->setResponse(new Entities\Album($params['row']));
                        break;
                }
            }
        });

        /**
         * Delete action event handler for images
         */
        Dispatcher::register('entity:delete', 'object:image', function (Event $event) {
            $params = $event->getParameters();

            /** @var Entities\Image $entity */
            $entity = $params['entity'];

            $event->setResponse($entity->delete());
        });

        /**
         * Delete action event handler for videos
         */
        Dispatcher::register('entity:delete', 'object:video', function (Event $event) {
            $params = $event->getParameters();

            /** @var Entities\Video $entity */
            $entity = $params['entity'];

            $event->setResponse($entity->delete());
        });
    }
}
