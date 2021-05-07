<?php
namespace Minds\Core\Notifications;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Common\Repository\Response;
use Minds\Core\Notifications\Repository;
use Minds\Core\Notifications\Delegates\PushSettingsDelegate;
use Minds\Core\Notifications\Delegates\CounterDelegate;
use Minds\Core\Notifications\Notification;
use Minds\Exceptions\UserErrorException;
use Exception;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;

/**
 * Notifications Manager
 * @package Minds\Core\Notifications
 */
class Manager
{
    /** @var Config $config */
    private $config;

    /** @var Repository $repository */
    private $repository;

    /** @var CounterDelegate $counters */
    private $counters;

    /** @var PushSettingsDelegate $settings */
    protected $settings;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var ACL */
    protected $acl;

    /** @var User $user */
    private $user;


    public function __construct(
        $config = null,
        $repository = null,
        $counters = null,
        $settings = null,
        $entitiesBuilder = null,
        $acl = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->repository = $repository ?: new Repository;
        $this->counters = $counters ?? new CounterDelegate;
        $this->settings = $settings ?? new PushSettingsDelegate;
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->acl = $acl ?? Di::_()->get('Security\ACL');
    }

    /**
     * Set the user to return notifications for
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }


    /**
     * Get push notification settings for user
     * @param string $userGuid
     * @return number
     */
    public function getSettings(string $userGuid)
    {
        if (!$userGuid) {
            $userGuid = Core\Session::getLoggedinUserGuid();
        }

        $this->settings->setUserGuid($userGuid);

        return $this->settings->getToggles();
    }

    /**
     * Update push notification settings for user
     * @param string $userGuid
     * @param string $id
     * @param string $toggle
     * @return boolean
     * @throws UserErrorException
     */
    public function updateSettings(string $userGuid, string $id, string $toggle): bool
    {
        if (!$userGuid || !$id || !$toggle) {
            throw new UserErrorException("userGuid, id and toggle must be provided");
        }

        $this->settings
            ->setToggle($id, $toggle)
            ->setUserGuid($userGuid)
            ->save();

        return true;
    }

    /**
     * Return unread count
     * @return int
     */
    public function getCount(): int
    {
        return $this->counters
            ->setUser($this->user)
            ->getCount();
    }

    /**
     * Return a single notification
     * @param $urn
     * @return Notification
     */
    public function getSingle($urn)
    {
        if (strpos($urn, 'urn:') === false) {
            $urn = "urn:notification:" . implode('-', [
                    $this->user->getGuid(),
                    $urn
                ]);
        }

        $response = $this->prepareForExport([$this->repository->get($urn)]);

        return $response[0] || null;
    }

    /**
     * Return a list of notifications
     * @param $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'to_guid' => $this->user ? $this->user->getGuid() : null,
            'type' => null,
            'types' => null,
            'limit' => 12,
            'offset' => '',
        ], $opts);

        $opts['type_group'] = $opts['type'];

        switch ($opts['type']) {
            case "tags":
                $opts['types'] = [
                    'tag',
                ];
                break;
            case "subscriptions":
                $opts['types'] = [
                    'friends',
                    'welcome_chat',
                    'welcome_discover',
                    'referral_ping',
                    'referral_pending',
                    'referral_complete',
                ];
                break;
            case "groups":
                $opts['types'] = [
                    'group_invite',
                    'group_kick',
                    'group_activity',
                ];
                break;
            case "comments":
                $opts['types'] = [
                    'comment',
                ];
                break;
            case "votes":
                $opts['types'] = [
                    'like',
                    'downvote',
                ];
                break;
            case "reminds":
                $opts['types'] = [
                    'remind',
                ];
                break;
            case "boosts":
                $opts['types'] = [
                    'boost_gift',
                    'boost_submitted',
                    'boost_submitted_p2p',
                    'boost_request',
                    'boost_rejected',
                    'boost_revoked',
                    'boost_accepted',
                    'boost_completed',
                    'boost_peer_request',
                    'boost_peer_accepted',
                    'boost_peer_rejected',
                    'welcome_points',
                    'welcome_boost',
                ];
                break;
        }

        $response = $this->repository->getList($opts);
        return $this->prepareForExport($response);
    }


    /**
     * Add notification to datastores
     * @param Notification $notification
     * @return string|false
     */
    public function add($notification)
    {
        try {
            $this->repository->add($notification);

            return $notification->getUuid();
        } catch (\Exception $e) {
            error_log($e);
            if (php_sapi_name() === 'cli') {
                //exit;
            }
        }
    }

    /**
     * Deletes notification from datastore
     * @param Notification $notification
     * @return bool
     */
    public function delete($notification): bool
    {
        return $this->repository->delete($notification);
    }

    /**
     * @param $type
     * @return string
     */
    public static function getGroupFromType($type)
    {
        switch ($type) {
            case 'tag':
                return 'tags';
                break;
            case 'friends':
            case 'welcome_chat':
            case 'welcome_discover':
            case 'referral_ping':
            case 'referral_pending':
            case 'referral_complete':
                return 'subscriptions';
                break;
            case 'group_invite':
            case 'group_kick':
            case 'group_activity':
                return 'groups';
                break;
            case 'comment':
                return 'comments';
                break;
            case 'like':
            case 'downvote':
                return 'votes';
                break;
            case 'remind':
                return 'reminds';
                break;
            case 'boost_gift':
            case 'boost_submitted':
            case 'boost_submitted_p2p':
            case 'boost_request':
            case 'boost_rejected':
            case 'boost_revoked':
            case 'boost_accepted':
            case 'boost_completed':
            case 'boost_peer_request':
            case 'boost_peer_accepted':
            case 'boost_peer_rejected':
            case 'welcome_points':
            case 'welcome_boost':
                return 'boosts';
                break;
        }
        return 'unknown';
    }

    /**
     * @param Response $notifications
     * @return array
     * @throws Exception
     */
    public function prepareForExport($notifications)
    {
        $return  = [];

        if (!$notifications || !Core\Session::getLoggedinUser()) {
            return $return;
        }

        foreach ($notifications as $i => $notif) {
            if ($notif->getToGuid() != Core\Session::getLoggedInUser()->guid) {
                error_log('[notification]: Mismatch of to_guid with uuid ' . $notif->getUuid());
                continue;
            }

            $entityObj = $this->entitiesBuilder->single($notif->getEntityGuid());
            $fromObj = $this->entitiesBuilder->single($notif->getFromGuid());

            $toObj = Core\Session::getLoggedInUser();
            $data = $notif->getData();

            try {
                if (
                    ($notif->getEntityGuid() && !$entityObj)
                    || ($entityObj && !$this->acl->read($entityObj, $toObj))
                    || ($notif->getFromGuid() && !$fromObj)
                    || !$this->acl->read($fromObj, $toObj)
                    || !$this->acl->interact($toObj, $fromObj)
                ) {
                    $this->manager->delete($notif);
                    unset($notifications[$i]);
                    continue;
                }
            } catch (\Exception $e) {
                unset($notifications[$i]);
                continue;
            }

            $notification = [
                'guid' => $notif->getUuid(),
                'uuid' => $notif->getUuid(),
                'description' => $data['description'],
                'entityObj' => $entityObj ? $entityObj->export() : null,
                'filter' => $notif->getType(),
                'fromObj' => $fromObj ? $fromObj->export() : null,
                'from_guid' => $notif->getFromGuid(),
                'to' => $toObj ? $toObj->export() : null,
                'guid' => $notif->getUuid(),
                'notification_view' => $notif->getType(),
                'params' => $data, // possibly some deeper polyfilling needed here,
                'time_created' => $notif->getCreatedTimestamp(),
            ];

            $notification['entity'] = $notification['entityObj'];

            $notification['owner'] =
            $notification['ownerObj'] =
            $notification['from'] =
            $notification['fromObj'];

            if ($entityObj && $entityObj->getType() == 'comment') {
                $parent = $this->entitiesBuilder->single(($data['parent_guid']));
                if ($parent) {
                    $notification['params']['parent'] = $parent->export();
                }
            }

            if ($notification['params']['group_guid']) {
                $group = $this->entitiesBuilder->single($notification['params']['group_guid']);
                if (!$group) {
                    unset($notifications[$i]);
                    continue;
                }
                $notification['params']['group'] = $group->export();
            }

            $return[$i] = $notification;
        }
        return array_values($return);
    }
}
