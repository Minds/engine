<?php
namespace Minds\Core\Media;

use Minds\Core;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Entities;
use Minds\Entities\Image;
use Minds\Entities\Video;
use Minds\Entities\Activity;
use Minds\Helpers;

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

        /**
         * ACL for when images have activity as container_guid
         */
        Dispatcher::register('acl:read', 'object', function (Event $event) {
            $params = $event->getParameters();

            $entity = $params['entity'];
            $user = $params['user'];

            if ($entity instanceof Image) {
                $container = $entity->getContainerEntity();

                if ($container
                    && ($container instanceof Activity || $container instanceof Video || $container instanceof Image)
                ) {
                    $canReadContainer = Core\Security\ACL::_()->read($container, $user);
                    $event->setResponse($canReadContainer);
                }
            }
        });

        Dispatcher::register('export:extender', 'activity', function (Event $event) {
            $params = $event->getParameters();
            /** @var Activity */
            $entity = $params['entity'];
            $export = $event->response() ?: [];

            $export['thumbnails'] = $entity->getThumbnails();

            switch ($entity->custom_type) {
                case 'video':
                    if ($entity->custom_data['guid']) {
                        $export['play:count'] = Helpers\Counters::get($entity->custom_data['guid'], 'plays');
                    }
                    $export['custom_data'] = $entity->getCustom()[1];
                    $export['thumbnail_src'] = $export['custom_data']['thumbnail_src'];
                    break;
                case 'batch':
                    $export['custom_data'] = $entity->getCustom()[1];
                    // fix old images src
                    if (is_array($export['custom_data']) && strpos($export['custom_data'][0]['src'], '/wall/attachment') !== false) {
                        $export['custom_data'][0]['src'] = Core\Config::_()->cdn_url . 'fs/v1/thumbnail/' . $entity->entity_guid;
                        $entity->custom_data[0]['src'] = $export['custom_data'][0]['src'];
                    }
                    // go directly to cdn
                    $src = $export['thumbnails']['xlarge'];
                    $export['custom_data'][0]['src'] = $src;
                    $export['thumbnail_src'] = $src;
                    break;
            }

            $event->setResponse($export);
        }, 501);
    }
}
