<?php

namespace Minds\Core\Notification\Extensions;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Queue\Client as QueueClient;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Interfaces;

class Push implements Interfaces\NotificationExtensionInterface
{
    /**
     * Singleton instance
     * @var Push
     */
    public static $_;

    /**
     * Sends data to the Push queue
     * @param array $notification
     * @return mixed
     */
    public function queue(array $notification = [])
    {
        $notification = array_merge([
            'exchange' => Di::_()->get('Config')->get('queue')['exchange'],
            'queue' => 'Push',
            'uri' => null,
            'to' => null,
            'toObj' => null,
            'from' => null,
            'params' => [],
        ], $notification);

        // TODO: [emi] should I throw an \Exception?
        if (!$notification['uri'] || !$notification['to']) {
            return false;
        }

        $notification['toObj'] = Di::_()->get('EntitiesBuilder')->single($notification['to']);

        if ($notification['params']['notification_view'] == 'like' || $notification['params']['notification_view'] == 'downvote') {
            return false;
        }

        $entity_guid = $notification['notification']->getEntityGuid();
        $entity = EntitiesFactory::build($entity_guid);

        $entity_type = 'object';
        $child_guid = '';
        $parent_guid = '';

        if (method_exists($entity, 'getGuid')) {
            $entity_guid = $entity->getGuid();
        } elseif (isset($entity->guid)) {
            $entity_guid = $entity->guid;
        }

        if ($entity->type === 'comment') {
            $parent_guid = $entity->getEntityGuid();
        } elseif (isset($entity->parent_guid)) {
            $parent_guid = $entity->parent_guid;
        }

        if (isset($entity->entity_guid)) {
            $child_guid = $entity->entity_guid;
        }

        if (method_exists($entity, 'getType')) {
            $entity_type = $entity->getType();
        } elseif (isset($entity->type)) {
            $entity_type = $entity->type;
        }

        if (method_exists($entity, 'getSubtype') && $entity->getSubtype()) {
            $entity_type .= ':' . $entity->getSubtype();
        } elseif (isset($entity->subtype) && $entity->subtype) {
            $entity_type .= ':' . $entity->subtype;
        }

        if (!$entity_guid && isset($notification['params']['entity_guid'])) {
            $entity_guid = $notification['params']['entity_guid'];
            $child_guid = '';
            $entity_type = '';
            $parent_guid = '';
        }

        $push = [
            'user_guid' => $notification['to'],
            'entity_guid' => $entity_guid,
            'child_guid' => $child_guid,
            'entity_type' => $entity_type,
            'parent_guid' => $parent_guid,
            'type' => $notification['params']['notification_view'],
            'uri' => $notification['uri'],
            'badge' => $notification['count'],
        ];

        $from_user = EntitiesFactory::build($notification['from'], ['cache' => true]) ?:
            Core\Session::getLoggedInUser();

        if (!$from_user) {
            return;
        }

        $push['title'] = 'Minds';
        $push['message'] = static::buildNotificationMessage($notification, $from_user, $entity);
        $push['large_icon'] = static::getNotificationLargeIcon($notification, $from_user);
        $push['big_picture'] = static::getNotificationBigPicture($notification, $from_user, $entity);
        $push['group'] = static::getNotificationGroup($notification, $from_user, $entity);

        return QueueClient::build()
            ->setExchange($notification['exchange'])
            ->setQueue($notification['queue'])
            ->send($push);
    }

    /**
     * [NOT USED]
     * @param array $notification
     * @return boolean
     */
    public function send(array $notification = [])
    {
        return false;
    }

    /**
     * [NOT USED]
     * @return boolean
     */
    public function run()
    {
        return false;
    }

    /**
     * Get the group for the notification
     * @param array $notification
     * @param mixed $from_user
     * @param mixed $entity
     * @return string
     */
    protected static function getNotificationGroup(array $notification = [], $from_user, $entity)
    {
        return $notification['uri'];
    }

    /**
     * Get the big picture for the notification
     * @param array $notification
     * @param mixed $from_user
     * @param mixed $entity
     * @return string
     */
    protected static function getNotificationBigPicture(array $notification = [], $from_user, $entity)
    {
        switch ($notification['params']['notification_view']) {
            case 'tag':
                if (!empty($entity->custom_data)) {
                    return $entity->custom_data[0]['src'];
                }
            // no break
            default:
                return null;

        }
    }

    /**
     * Get the large icon for the notification
     * @param array $notification
     * @param mixed $from_user
     * @return string
     */
    protected static function getNotificationLargeIcon(array $notification = [], $from_user)
    {
        switch ($notification['params']['notification_view']) {
            case 'boost_request':
            case 'boost_accepted':
            case 'boost_rejected':
            case 'boost_revoked':
            case 'boost_completed':
                return null;
            default:
                return $from_user->getIconURL('medium');

        }
    }


    /**
     * Creates a human-readable notification message
     * @param array $notification
     * @param mixed $from_user
     * @param mixed $entity
     * @return string
     */
    public static function buildNotificationMessage(array $notification = [], $from_user, $entity)
    {
        /** @var Core\I18n\Translator $translator */
        $translator = Di::_()->get('I18n\Translator');
        $translator->setLocale($notification['toObj']->getLanguage());

        $message = '';

        $data = $notification['notification']->getData();

        if (!isset($notification['params']['notification_view'])) {
            return $message;
        }

        $title = htmlspecialchars_decode($entity->title);

        $name = $from_user->name;

        $isOwner = $notification['to'] == $entity->owner_guid;

        $desc = 'a.post';
        if ($entity->type == 'activity') {
            $desc = 'activity';
        } elseif (isset($entity->subtype)) {
            $desc = $entity->subtype;
        } elseif ($isOwner || isset($entity->ownerObj['name'])) {
            $desc = 'post';
        }

        $boostDescription = $entity->title ?: $entity->name ?: ($entity->type !== 'user' ? 'post' : 'channel');

        switch ($notification['params']['notification_view']) {

            case 'comment':
                $owner = $isOwner ? 'your' : 'user';
                return $translator->trans("comment.{$owner}.{$desc}", ['%user%' => $name]);

            case 'like':
                $type = static::getEntityType($entity);

                $params = [
                    '%user%' => $name,
                ];

                if ($title && static::getEntityType($entity) !== 'comment') {
                    $params['%title%'] = $title;
                    $type = 'title';
                }

                return $translator->trans("like.{$type}", $params);

            case 'tag':
                if ($entity->type === 'comment') {
                    return $translator->trans('tag.comment', ['%user%' => $name]);
                } else {
                    return $translator->trans('tag.post', ['%user%' => $name]);
                }

                // no break
            case 'friends':
                return $translator->trans('user.subscribed', ['%user%' => $name]);

            case 'remind':
                return $translator->trans('remind.' . $desc, ['%user%' => $name]);

            case 'boost_gift':
                return $translator->trans('boost.gift', ['%user%' => $name]);

            case 'boost_request':
                return $translator->trans('boost.request', ['%user%' => $name, '%points%' => $data['points']]);

            case 'boost_accepted':
                return $translator->trans('boost.accepted', [
                    '%impressions%' => $data['impressions'],
                    '%description%' => $boostDescription,
                ]);

            case 'boost_rejected':
                return $translator->trans('boost.rejected', ['%description%' => $boostDescription]);

            case 'boost_revoked':
                return $translator->trans('boost.revoked', ['%description%' => $boostDescription]);

            case 'boost_completed':
                return $translator->trans('boost.completed', [
                    '%impressions%' => $data['impressions'],
                    '%totalImpressions%' => $data['impressions'],
                    '%description%' => $boostDescription,
                ]);

            case 'group_invite':
                return $translator->trans('group.invite', ['%user%' => $name, '%group%' => $data['group']['name']]);

            case 'messenger_invite':
                return $translator->trans('messenger.invite', ['%user%' => $name]);

            case 'referral_ping':
                return $translator->trans('referral.ping', ['%user%' => $name]);

            case 'referral_pending':
                return $translator->trans('referral.pending', ['%user%' => $name]);

            case 'referral_complete':
                return $translator->trans('referral.complete', ['%user%' => $name]);

            default:
                return "";
        }
    }

    /**
     * Factory builder
     */
    public static function _()
    {
        if (!self::$_) {
            self::$_ = new self();
        }
        return self::$_;
    }

    private static function getEntityType($entity)
    {
        return $entity->type !== 'object' ? $entity->type : $entity->subtype;
    }
}
