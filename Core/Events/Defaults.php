<?php
/**
 * Default event listeners.
 */

namespace Minds\Core\Events;

use Minds\Core;
use Minds\Core\Analytics\Metrics;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Helpers;

class Defaults
{
    private static $_;

    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ExperimentsManager $experiments = null,
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->experiments ??= Di::_()->get('Experiments\Manager');

        $this->init();
    }

    public function init()
    {
        //Channel object reserializer
        Dispatcher::register('export:extender', 'all', function ($event) {
            $params = $event->getParameters();
            $cacheTtl = 259200; // Cache for 3 days.

            if ($params['entity'] instanceof Core\Blogs\Blog) {
                return;
            }

            $export = $event->response() ?: [];

            if (!$params['entity'] instanceof User) {
                if ($params['entity']->getOwnerGuid()) {
                    $ownerObj = $this->entitiesBuilder->single($params['entity']->getOwnerGuid(), [
                        'cache' => true,
                        'cacheTtl' => $cacheTtl
                    ]);
                    if ($ownerObj) {
                        $export['ownerObj'] = $ownerObj->export();
                    }
                }

                if (!$params['entity'] instanceof Group) {
                    if ($params['entity']->getContainerGuid() !== $params['entity']->getOwnerGuid()) {
                        $containerObj = $this->entitiesBuilder->single($params['entity']->getContainerGuid(), [
                            'cache' => true,
                            'cacheTtl' => $cacheTtl
                        ]);
                        if ($containerObj instanceof Group) {
                            $export['containerObj'] = $containerObj->export();
                        }
                    }
                }
            }

            $event->setResponse($export);
        });

        // Decode special characters and strip tags.
        Dispatcher::register('export:extender', 'all', function ($event) {
            $export = $event->response() ?: [];
            $params = $event->getParameters();

            if ($params['entity'] instanceof Core\Blogs\Blog) {
                return; // do not sanitize for blogs
            }

            $allowedTags = '';
            if (isset($export['message'])) {
                $export['message'] = strip_tags(
                    htmlspecialchars_decode($export['message']),
                    $allowedTags
                );
            }

            if (isset($export['description'])) {
                $export['description'] = strip_tags(
                    htmlspecialchars_decode($export['description']),
                    $allowedTags
                );
            }

            $event->setResponse($export);
        });

        //Comments count export extender
        Dispatcher::register('export:extender', 'all', function ($event) {
            $params = $event->getParameters();

            $export = $event->response() ?: [];

            if (!($params['entity']->type === 'object'
                || $params['entity']->type === 'group'
                || $params['entity']->type === 'activity')) {
                return false;
            }

            /** @var Core\Data\cache\abstractCacher $cacher */
            $cacher = Core\Di\Di::_()->get('Cache');

            if (($params['entity']->type == 'activity') && $params['entity']->entity_guid) {
                $guid = $params['entity']->entity_guid;
            } else {
                $guid = $params['entity']->guid;
            }

            $cached = $cacher->get("comments:count:$guid");
            if ($cached !== false) {
                $count = $cached;
            } else {
                $manager = new Core\Comments\Manager();
                $count = $manager->count($guid);
                $cacher->set("comments:count:$guid", $count);
            }

            $export['comments:count'] = $count;

            $event->setResponse($export);
        });

        Dispatcher::register('delete', 'all', function ($e) {
            $params = $e->getParameters();
            $entity = $params['entity'];

            if (!$entity) {
                return;
            }

            $event = new Metrics\Event();
            $event->setType('action')
                ->setProduct('platform')
                ->setUserGuid((string) Core\Session::getLoggedInUserGuid())
                ->setAction('delete')
                ->setEntityGuid((string) $entity->guid)
                ->setEntityType($entity->type)
                ->setEntitySubtype($entity->subtype)
                ->setEntityOwnerGuid((string) $entity->owner_guid)
                ->push();

            $e->setResponse(true);
        });

        // Notifications events
        Core\Notification\Events::registerEvents();

        (new Core\Events\Hooks\Register())->init();

        // Subscription Queue events
        Helpers\Subscriptions::registerEvents();

        // Payments events
        (new Core\Payments\Events())->register();

        // Media events
        (new Core\Media\Events())->register();

        // Wire Events
        (new Core\Wire\Events())->register();

        // Report Events
        (new Core\Reports\Events())->register();

        // Group Events
        (new Core\Groups\Events())->register();

        // Blog events
        (new Core\Blogs\Events())->register();

        // Messenger Events
        (new Core\Messenger\Events())->setup();

        // Blockchain events
        (new Core\Blockchain\Events())->register();

        // Comments events
        (new Core\Comments\Events())->register();

        // Channels events
        (new Core\Channels\Events())->register();

        // Feeds events
        (new Core\Feeds\Events())->register();

        // Entities events
        (new Core\Entities\Events())->register();

        // Supermind events
        (new Core\Supermind\Events\Events())->register();

        // Activity Events
        (new Core\Feeds\Activity\Events())->register();

        // Boost Events
        (new Core\Boost\V3\Events\Events())->register();

        // Chat ACL Events
        (new Core\Chat\Events\Events(
            eventsDispatcher: Di::_()->get('EventsDispatcher'),
        ))->register();
    }

    public static function _()
    {
        if (!self::$_) {
            self::$_ = new Defaults();
        }

        return self::$_;
    }
}
