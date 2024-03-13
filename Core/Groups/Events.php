<?php
/**
 * Events listeners for Groups
 */
namespace Minds\Core\Groups;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Groups\Delegates\ElasticSearchDelegate;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Entities\Group as GroupEntity;
use Minds\Entities\Activity;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Core\Session;
use Minds\Entities\EntityInterface;
use Minds\Exceptions\NotFoundException;

class Events
{
    protected V2\Membership\Manager $membershipManager;

    /**
     * Initialize events
     */
    public function register()
    {
        Dispatcher::register('entities_class_loader', "elgg/hook/all", function ($hook, $type, $return, $row) {
            if (property_exists($row, 'type') && $row->type == 'group') {
                $entity = new GroupEntity();
                $entity->loadFromArray((array) $row);

                return $entity;
            }
        });

        /**
         * Members can read group entities.
         * Moderators can read pending group posts
         */
        Dispatcher::register('acl:read', 'all', function ($e) {
            $params = $e->getParameters();
            $entity = $params['entity'];


            if (!$entity instanceof EntityInterface) {
                return;
            }
        
            $access_id = $entity->getAccessId();
            $user = $params['user'];

            if (!method_exists($entity, 'getContainerEntity')) {
                return;
            }

            $group = $entity->getContainerEntity();

            if ($group instanceof GroupEntity) {
                if ($entity instanceof Activity && $entity->getPending()) {
                    try {
                        $membership = $this->getGroupMembershipManager()->getMembership($group, $user);
                        $e->setResponse($membership->isModerator());
                    } catch (NotFoundException $ex) {
                        $e->setResponse(false);
                    }
                    return;
                }

                if ($group->isPublic()) {
                    $e->setResponse(true);
                    return;
                }

                try {
                    if (!$user) {
                        return;
                    }
                    $membership = $this->getGroupMembershipManager()->getMembership($group, $user);
                    $e->setResponse($membership->isMember());
                } catch (NotFoundException $ex) {
                }
            }
        });

        /**
         * Group moderators have write permissions for entities belonging to the group
         */
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

            try {
                $membership = $this->getGroupMembershipManager()->getMembership($group, $user);
                $e->setResponse($membership->isModerator());
            } catch (NotFoundException $ex) {
            }
        });

        /**
         * Group moderators have write permissions on comments made in a group
         */
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

            try {
                $membership = $this->getGroupMembershipManager()->getMembership($group, $user);
                $e->setResponse($membership->isModerator());
            } catch (NotFoundException $ex) {
            }
        });

        /**
         * Group members can interact with group activity
         */
        Dispatcher::register('acl:interact', 'activity', function ($e) {
            $params = $e->getParameters();
            $activity = $params['entity'];
            $user = $params['user'];

            if ($activity instanceof Activity && $activity->container_guid && $activity->container_guid !== $activity->owner_guid) {
                $container = EntitiesFactory::build($activity->container_guid);

                if ($container instanceof GroupEntity) {
                    if ($container->isPublic()) {
                        $e->setResponse(true);
                        return;
                    }
                    try {
                        $membership = $this->getGroupMembershipManager()->getMembership($container, $user);
                        $e->setResponse($membership->isMember());
                    } catch (NotFoundException $ex) {
                    }
                }
            }
        });

        /**
         * When deleting an activity, remove it from the admin queue
         */
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

        /**
         * Group members can read the grup
         */
        Dispatcher::register('acl:read', 'group', function ($e) {
            $params = $e->getParameters();
            $group = $params['entity'];
            $user = $params['user'];

            // If public, everyone can read
            if ($group->isPublic()) {
                $e->setResponse(true);
                return;
            }

            // If logged out, and not public, then do not continue
            if (!$user) {
                return;
            }

            try {
                $membership = $this->getGroupMembershipManager()->getMembership($group, $user);
                $e->setResponse($membership->isMember());
            } catch (NotFoundException $ex) {
            }
        });

        /**
         * Moderators and group owner can write to the group
         */
        Dispatcher::register('acl:write', 'group', function ($e) {
            $params = $e->getParameters();
            $group = $params['entity'];
            $user = $params['user'];

            try {
                $membership = $this->getGroupMembershipManager()->getMembership($group, $user);
                $e->setResponse($membership->isModerator());
                return;
            } catch (NotFoundException $ex) {
            }

            $e->setResponse(false);
        });

        /**
         * Group members can edit their own group posts
         */
        Dispatcher::register('acl:write:container', 'group', function ($e) {
            $params = $e->getParameters();
            $group = $params['container'];
            $user = $params['user'];
            $entity = $params['entity'];

            try {
                $membership = $this->getGroupMembershipManager()->getMembership($group, $user);
            } catch (NotFoundException $ex) {
                return;
            }

            if ($membership->isOwner()) {
                return $e->setResponse(true);
            }

            // If member and we own the post
            if ($membership->isMember() && $entity->owner_guid == $user->guid) {
                return $e->setResponse(true);
            }
        });

        /**
         * Posts from none group moderators will go to the review queue
         */
        Dispatcher::register('activity:container:prepare', 'group', function ($e) {
            $params = $e->getParameters();

            $group = $params['container'];
            $activity = $params['activity'];

            $owner = Di::_()->get(EntitiesBuilder::class)->single($activity->getOwnerGuid());

            try {
                $membership = $this->getGroupMembershipManager()->getMembership($group, $owner);
            } catch (NotFoundException $ex) {
                return;
            }
            
            // The accessid of the activity should always be the group
            $activity->setAccessId($group->guid);

            if ($group->getModerated() && !$membership->isModerator()) {
                $key = "activity:container:{$group->guid}";
                $index = array_search($key, $activity->indexes, true);
                if ($index !== false) {
                    unset($activity->indexes[$index]);
                }

                $activity->setPending(true);
            }
        });

        /**
         * Sends the activity to the group review feed
         */
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

        Dispatcher::register('export:extender', 'group', function ($e) {
            $params = $e->getParameters();

            $group = $params['entity'];
            $user = Session::getLoggedinUser();

            if (!$group instanceof GroupEntity) {
                return;
            }

            $membershipManager = $this->getGroupMembershipManager();

            $export = $e->response() ?: [];

            try {
                if (!$user) {
                    throw new NotFoundException();
                }
                $membership = $membershipManager->getMembership($group, $user);
            } catch (NotFoundException $ex) {
                $membership = null;
            }

            $export['members:count'] = $membershipManager->getMembersCount($group);
            //$export['requests:count'] = $membershipManager->getRequestsCount();

            $export['is:owner'] = $membership?->isOwner() ?: false;
            $export['is:moderator'] = $membership?->isModerator() ?: false;
            $export['is:member'] = $membership?->isMember() ?: false;
            $export['is:creator'] = Session::isAdmin() || $group->isCreator(Session::getLoggedInUser());
            $export['is:awaiting'] = $membership?->membershipLevel === GroupMembershipLevelEnum::REQUESTED;

            $e->setResponse($export);
        });

        Dispatcher::register('entity:save', 'group', function ($e) {
            $params = $e->getParameters();
            $group = $params['entity'];

            $elasticSearchDelegate = new ElasticSearchDelegate();
            $elasticSearchDelegate->onSave($group);
        });
    }

    protected function getGroupMembershipManager(): V2\Membership\Manager
    {
        return $this->membershipManager ??= Di::_()->get(V2\Membership\Manager::class);
    }
}
