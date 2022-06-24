<?php
namespace Minds\Core\Notifications\Push\Settings;

use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\PushNotification;

class Manager
{
    /** @var Repository */
    protected $repository;

    public function __construct(Repository $repository = null)
    {
        $this->repository = $repository ?? new Repository();
    }

    /**
     * Verify if a notification can be sent
     * @param PushNotification $pushNotification
     * @return bool
     */
    public function canSend(PushNotification $pushNotification): bool
    {
        $opts = new SettingsListOpts();
        $opts->setUserGuid($pushNotification->getNotification()->getToGuid());
        foreach ($this->getList($opts) as $pushSetting) {
            if ($pushSetting->getNotificationGroup() === PushSetting::ALL && $pushSetting->getEnabled() === false) {
                return false; // User has turned off ALL notifications
            }

            if ($pushSetting->getNotificationGroup() === $pushNotification->getGroup() && $pushSetting->getEnabled() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param SettingsListOpts $opts
     * @return PushSetting[]
     */
    public function getList(SettingsListOpts $opts): array
    {
        /** @var PushSetting[]; */
        $defaults = array_map(function ($notificationGroup) {
            $pushSetting = new PushSetting();
            $pushSetting->setNotificationGroup($notificationGroup);
            return $pushSetting;
        }, array_keys(array_merge(NotificationTypes::TYPES_GROUPINGS, [PushSetting::ALL => []])));

        $keyValue = [];

        foreach ($this->repository->getList($opts) as $pushSetting) {
            $keyValue[$pushSetting->getNotificationGroup()] = $pushSetting;
        }

        foreach ($defaults as $pushSetting) {
            if (!isset($keyValue[$pushSetting->getNotificationGroup()])) {
                $keyValue[$pushSetting->getNotificationGroup()] = $pushSetting;
            }
        }

        return array_values($keyValue);
    }

    /**
     * Whether all push has been enabled for a given user GUID.
     * @param string $userGuid - user GUID to check for.
     * @return boolean - true if all push are enabled for user.
     */
    public function hasEnabledAll(string $userGuid): bool
    {
        $opts = (new SettingsListOpts())
            ->setUserGuid($userGuid);

        $pushSettings = $this->getList($opts);

        foreach ($pushSettings as $pushSetting) {
            if ($pushSetting->getNotificationGroup() === PushSetting::ALL && $pushSetting->getEnabled() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param PushSetting $pushSetting
     * @return bool
     */
    public function add(PushSetting $pushSetting): bool
    {
        return $this->repository->add($pushSetting);
    }
}
