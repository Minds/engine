<?php
/**
 * Events listeners for Groups
 */
namespace Minds\Core\Groups;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Entities\Group as GroupEntity;
use Minds\Entities\Activity;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Core\Session;

class Events
{
    /**
     * Initialize events
     */
    public function register()
    {
        Dispatcher::register('entities_class_loader', "elgg/hook/all", function ($hook, $type, $return, $row) {
            if ($row->type == 'group') {
                $entity = new GroupEntity();
                $entity->loadFromArray((array) $row);

                return $entity;
            }
        });

        Dispatcher::register('acl:read', 'all', function ($e) {
            $params = $e->getParameters();
            $entity = $params['entity'];
            $access_id = $entity->access_id;
            $user = $params['user'];

            $group = $entity->getContainerEntity();

            if ($group instanceof GroupEntity) {
                /** @var Membership $membership */
                $membership = Membership::_($group);

                if ($entity instanceof Activity && $entity->getPending()) {
                    $e->setResponse($group->isOwner($user->guid));
                    return;
                }

                $e->setResponse($group->isPublic() || $membership->isMember($user->guid));
            }
        });

        Dispatcher::register('acl:write', 'all', function ($e) {
            $params = $e->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            if (!method_exists($entity, 'getContainerEntity')) {
                return;
            }

            $group = $entity->getContainerEntity();

            if (!($group instanceof GroupEntity)) {
                return;
            }

            $e->setResponse(($group->isOwner($user->guid) || $group->isModerator($user->guid)) && $group->isMember($user->guid));
        });

        Dispatcher::register('acl:write', 'comment', function ($e) {
            $params = $e->getParameters();
            $comment = $params['entity'];
            $user = $params['user'];

            $entity = EntitiesFactory::build($comment->getEntityGuid());

            if (!($entity instanceof GroupEntity)) {
                if ($entity instanceof Activity && $entity->canEdit()) {
                    //TODO: refactor as this could potential catch non-groups
                    $e->setResponse(true);
                }
                return;
            }

            $group = $entity;
            $e->setResponse(($group->isOwner($user->guid) || $group->isModerator($user->guid)) && $group->isMember($user->guid));
        });

        Dispatcher::register('acl:interact', 'activity', function ($e) {
            $params = $e->getParameters();
            $activity = $params['entity'];
            $user = $params['user'];

            if ($activity instanceof Activity && $activity->container_guid && $activity->container_guid !== $activity->owner_guid) {
                $container = EntitiesFactory::build($activity->container_guid);

                if ($container->type === 'group') {
                    $e->setResponse($container->isPublic() || $container->isMember($user->guid));
                }
            }
        });

        Dispatcher::register('acl:interact', 'group', function ($e) {
            $params = $e->getParameters();
            $group = $params['entity'];
            $user = $params['user'];
            $interaction = $params['interaction'];

            if ($group instanceof GroupEntity && $interaction === 'comment') {
                $e->setResponse($group->isMember($user->guid));
            }
        });

        Dispatcher::register('delete', 'activity', function ($e) {
            $params = $e->getParameters();
            $activity = $params['entity'];

            if (!$activity) {
                return;
            }

            $group = $activity->getContainerEntity();

            if (!($group instanceof GroupEntity)) {
                return;
            }

            /** @var Groups\AdminQueue $adminQueue */
            $adminQueue = Di::_()->get('Groups\AdminQueue');
            $adminQueue->delete($group, $activity);
        });

        Dispatcher::register('acl:read', 'group', function ($e) {
            $params = $e->getParameters();
            $group = $params['entity'];
            $user = $params['user'];

            $e->setResponse($group->isPublic() || $group->isMember($user->guid));
        });

        Dispatcher::register('acl:write', 'group', function ($e) {
            $params = $e->getParameters();
            $group = $params['entity'];
            $user = $params['user'];

            $isOwner = $group->isOwner($user->guid);
            $isModerator = $group->isModerator($user->guid);
            $isMember = $group->isMember($user->guid);

            if ($isOwner && $isMember) {
                $e->setResponse(true);
                return;
            } elseif ($isModerator && $isMember) {
                $e->setResponse(true);
                return;
            }

            $e->setResponse(false);
        });

        Dispatcher::register('acl:write:container', 'group', function ($e) {
            $params = $e->getParameters();
            $group = $params['container'];
            $user = $params['user'];
            $entity = $params['entity'];

            if ($group->isOwner($user->guid)) {
                return $e->setResponse(true);
            }

            // If member and we own the post
            if ($group->isMember($user->guid) && $entity->owner_guid == $user->guid) {
                return $e->setResponse(true);
            }
        });

        Dispatcher::register('activity:container:prepare', 'group', function ($e) {
            $params = $e->getParameters();

            $group = $params['container'];
            $activity = $params['activity'];

            if ($group->getModerated() && !$group->isOwner($activity->owner_guid)) {
                $key = "activity:container:{$group->guid}";
                $index = array_search($key, $activity->indexes, true);
                if ($index !== false) {
                    unset($activity->indexes[$index]);
                }

                $activity->setPending(true);
            }
        });

        Dispatcher::register('activity:container', 'group', function ($e) {
            $params = $e->getParameters();

            $group = $params['container'];
            $activity = $params['activity'];

            if ($group->getModerated() && $activity->getPending()) {
                Di::_()->get('Groups\Feeds')
                    ->setGroup($group)
                    ->queue($activity);
            } else {
                (new Notifications())
                    ->setGroup($group)
                    ->setActor(Session::getLoggedInUser())
                    ->queue('activity');
            }
        });

        Dispatcher::register('cleanup:dispatch', 'group', function ($e) {
            $params = $e->getParameters();
            $e->setResponse(Membership::cleanup($params['group']));
        });

        Dispatcher::register('save', 'comment', function ($e) {
            $params = $e->getParameters();
            $comment = $params['entity'];

            if (!$comment->isGroupConversation()) {
                return;
            }

            $group = new GroupEntity;
            $group->setGuid($comment->getEntityGuid());

            (new Notifications())
                ->setGroup($group)
                ->setActor(Session::getLoggedInUser())
                ->queue('conversation');
        });
    }
}
