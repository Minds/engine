<?php
namespace Minds\Core\Notifications\Push\Settings;

use Minds\Core\Notifications\NotificationTypes;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method string getNotificationGroup()
 * @method self setEnabled(bool $enabled)
 * @method bool getEnabled()
 */
class PushSetting
{
    use MagicAttributes;

    /** @var string */
    const ALL = 'all';

    /** @var string */
    protected $userGuid;

    /** @var string */
    protected $notificationGroup;

    /** @var bool */
    protected $enabled = true;

    /** @var NotificationTypes */
    private $notificationTypes;

    public function __construct(NotificationTypes $notificationTypes = null)
    {
        $this->notificationTypes = $notificationTypes ?? new NotificationTypes;
    }

    /**
     * @param string
     * @return self
     */
    public function setNotificationGroup(string $notificationGroup): self
    {
        if (!isset($this->notificationTypes->getTypesGroupings()[$notificationGroup]) && $notificationGroup != self::ALL) {
            throw new \Exception("NotificationGroup $notificationGroup not found in NotificationTypes::TYPES_GROUPINGS");
        }
        $this->notificationGroup = $notificationGroup;
        return $this;
    }

    public function export(): array
    {
        return [
            'notification_group' => $this->notificationGroup,
            'enabled' => $this->enabled,
        ];
    }
}
