<?php
namespace Minds\Core\Notifications;

use Minds\Common\Repository\AbstractRepositoryOpts;

//ojm pin
/**
 * @method self setToGuid(string $toGuid)
 * @method string getToGuid()
 * @method self setLimit(int $limit)
 * @method int getLimit()
 * @method self setOffset(string $offset)
 * @method int getOffset()
 * @method self setUuid(string $uuid)
 * @method string getUuid()
 * @method self setMerge(bool $merge)
 * @method bool getMerge()
 * @method self setGroupingType(string $type)
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
    protected $uuid;

    /** @var bool */
    protected $merge = true;

    /** @var string */
    protected $groupingType;

    /**
     * Set the type
     * @param string $groupingType
     * @return self
     */
    public function setGroupingType(string $groupingType): self
    {
        if (!isset(NotificationTypes::TYPES_GROUPINGS[$groupingType])) {
            throw new \Exception("GroupingType $groupingType not found in NotificationTypes::TYPES_GROUPINGS");
        }
        $this->groupingType = $groupingType;
        return $this;
    }
}
