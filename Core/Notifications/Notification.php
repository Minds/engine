<?php
/**
 * Notification Entity
 */
namespace Minds\Core\Notifications;

use Cassandra\Timeuuid;
use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * Class Notification
 * @package Minds\Core\Notification
 * @method string getUuid()
 * @method Notification setUuid(string $value)
 * @method string getToGuid()
 * @method Notification setToGuid(string $value)
 * @method string getFromGuid()
 * @method Notification setFromGuid(string $value)
 * @method string getEntityUrn()
 * @method Notification getEntityUrn(string $value)
 * @method string getType()
 * @method Notification setType(string $value)
 * @method array getData()
 * @method Notification setData($value)
 * @method int getCreatedTimestamp()
 * @method Notification setCreatedTimestamp(int $value)
 * @method int getReadTimestamp()
 * @method Notification setReadTimestamp(int $value)
 */
class Notification
{
    use MagicAttributes;

    /** @param string */
    private $uuid;

    /** @param string */
    private $toGuid;

    /** @param string */
    private $fromGuid;

    /** @param string */
    private $entityUrn;

    /** @param string */
    private $type;

    /** @param array */
    private $data;

    /** @var int */
    private $createdTimestamp;

    /** @var int */
    private $readTimestamp;

    /** @var string[] */
    private $mergedFromGuids = [];

    /** @var int */
    private $mergedCount = 0;

    /** @var EntitesBuilder */
    private $entitiesBuilder;

    /** @var Resolver */
    private $entitiesRevolver;

    public function __construct(EntitiesBuilder $entitiesBuilder = null, Resolver $resolver = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->entitiesRevolver = $resolver ?? new Resolver();
    }

    /**
     * Return the UUID of the notification
     * @return string
     */
    public function getUrn(): string
    {
        return "urn:notification:" . implode('-', [ $this->toGuid, $this->getUuid() ]);
    }

    /**
     * Return the uuid, create if not exists
     * @return string
     */
    public function getUuid(): string
    {
        if (!$this->uuid) {
            $this->uuid = (new Timeuuid())->uuid();
        }
        return $this->uuid;
    }

    /**
     * Return a hydrated 'to' user
     * @return User
     */
    public function getTo(): ?User
    {
        $to = $this->entitiesBuilder->single($this->getToGuid());
        if ($to instanceof User) {
            return $to;
        }
        return null;
    }

    /**
     * Return a hydrated 'from' user
     * @return User
     */
    public function getFrom(): ?User
    {
        $from = $this->entitiesBuilder->single($this->getFromGuid());
        if ($from instanceof User) {
            return $from;
        }
        return null;
    }

    /**
     * Return hydrated 'from' users
     * @param int $limit
     * @return User[]
     */
    public function getMergedFrom($limit = 1): array
    {
        $mergedFrom = [];
        foreach (array_slice($this->mergedFromGuids, 0, $limit) as $fromGuid) {
            $from = $this->entitiesBuilder->single($fromGuid);
            if ($from instanceof User) {
                $mergedFrom[] = $from;
            }
        }
        return $mergedFrom;
    }

    /**
     * Return a hydrated 'from' user
     * @return User
     */
    public function getEntity()
    {
        try {
            $entity = $this->entitiesRevolver
            //->setOpts(['asActivities' => true,])
            ->single(new Urn($this->getEntityUrn()));

            if ($entity) {
                return $entity;
            }
        } catch (\Exception $e) {
        }
        return null;
    }

    /**
     * Return the groupable type
     * @return string
     */
    public function getGroupType(): string
    {
        foreach (NotificationTypes::TYPES_GROUPS as $groupType => $types) {
            if (in_array($this->type, $types, true)) {
                return $groupType;
            }
        }
    }

    /**
     * Get the merge key
     * @return string
     */
    public function getMergeKey(): string
    {
        $period = 3600 * 48; // 24 hour blocks
        $nearestPeriod = $this->getCreatedTimestamp() - ($this->getCreatedTimestamp() % $period);

        return hash('sha256', $nearestPeriod . $this->getEntityUrn() . $this->getType());
    }

    /**
     * Export
     * @return array
     */
    public function export(): array
    {
        $from = $this->getFrom();
        $entity = $this->getEntity();
        $mergedFromExported = array_map(function ($from) {
            return $from->export();
        }, $this->getMergedFrom(1));
        return [
            'uuid' => $this->getUuid(),
            'urn' => $this->getUrn(),
            'to_guid' => $this->getToGuid(),
            'from_guid' => $this->getFromGuid(),
            'from' => $from ? $from->export() : null,
            'entity_urn' => $this->getEntityUrn(),
            'entity' => $entity ? $entity->export() : null,
            'read' => $this->getReadTimestamp() > $this->getCreatedTimestamp(),
            'created_timestamp' => $this->getCreatedTimestamp(),
            'type' => $this->getType(),
            'data' => $this->getData(),
            'merged_from_guids' => $this->getMergedFromGuids(),
            'merged_from' => $mergedFromExported,
            'merged_count' => $this->getMergedCount(),
            'merge_key' => $this->getMergeKey(),
        ];
    }
}
