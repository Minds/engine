<?php
namespace Minds\Core\Notifications;

use Minds\Common\Repository\AbstractRepositoryOpts;

/**
 * @method self setToGuid(string $toGuid)
 * @method string getToGuid()
 * @method self setLimit(int $limit)
 * @method int getLimit()
 * @method self setOffset(string $offset)
 * @method int getOffset()
 * @method self setUuid(string $uuid)
 * @method string getUuid()
 * @method self setLtUuid(string $uuid)
 * @method string getLtUuid()
 * @method self setMerge(bool $merge)
 * @method bool getMerge()
 * @method self setGroupType(string $type)
 */
class NotificationsListOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $toGuid;

    /** @var int */
    protected $limit = 24;

    /** @var string */
    protected $offset = "";

    /** @var string */
    protected $ltUuid;

    /** @var string */
    protected $uuid;

    /** @var bool */
    protected $merge = true;

    /** @var string */
    protected $groupType;

    /**
     * Set the type
     * @param string $groupType
     * @return self
     */
    public function setGroupType(string $groupType): self
    {
        if (!isset(NotificationTypes::TYPES_GROUPS[$groupType])) {
            throw new \Exception("GroupType $groupType not found in NotificationTypes::TYPES_GROUPS");
        }
        $this->groupType = $groupType;
        return $this;
    }
}
