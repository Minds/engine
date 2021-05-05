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

    /** @var User $user */
    private $user;

    /** @var PushSettingsDelegate $settings */
    protected $settings;

    public function __construct(
        $config = null,
        $repository = null,
        $counters = null,
        $settings = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->repository = $repository ?: new Repository;
        $this->counters = $counters ?? new CounterDelegate;
        $this->settings = $settings ?? new PushSettingsDelegate;
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
        return $this->repository->get($urn);
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

        return $this->repository->getList($opts);
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
}
