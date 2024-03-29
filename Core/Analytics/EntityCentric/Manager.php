<?php
/**
 * EntityCentric Manager
 * @author Mark
 */

namespace Minds\Core\Analytics\EntityCentric;

use DateTime;
use Exception;

class Manager
{
    /** @var array */
    const SYNCHRONISERS = [
        EngagementSynchroniser::class,
        PartnerEarningsSynchroniser::class,
        SignupsSynchroniser::class,
        ReferralsSynchroniser::class,
        ActiveUsersSynchroniser::class,
        ViewsSynchroniser::class,
    ];

    /** @var Repository */
    protected $repository;

    /** @var Sums */
    protected $sums;

    /** @var int */
    private $from;

    /** @var int */
    private $to;

    public function __construct(
        $repository = null,
        $sums = null
    ) {
        $this->repository = $repository ?? new Repository();
        $this->sums = $sums ?? new Sums();
    }

    /**
     * @param int $from
     * @return self
     */
    public function setFrom($from): self
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Synchronise views from cassandra to elastic
     * @param array $opts - if singleTask is provided, will run only the given synchroniser class.
     * @return iterable
     */
    public function sync(array $opts = []): iterable
    {
        $synchroniserClasses = $this->getSynchroniserClasses($opts);

        foreach ($synchroniserClasses as $synchroniserClass) {
            $synchroniser = new $synchroniserClass;
            $date = (new DateTime())->setTimestamp($this->from);
            $synchroniser->setFrom($this->from);
            foreach ($synchroniser->toRecords() as $record) {
                $this->add($record);
                yield $record;
            }
            // Call again incase any leftover
            $this->repository->bulk();
        }
        echo "done";
    }

    /**
     * Add an entity centric record to the database
     * @param EntityCentricRecord $record
     * @return bool
     */
    public function add(EntityCentricRecord $record): bool
    {
        return (bool) $this->repository->add($record);
    }

    /**
     * Query aggregate
     * @param array $query
     * @return array
     */
    public function getAggregateByQuery(array $query): array
    {
        return [];
    }

    /**
     * @param array $opts
     * @retun iterable
     */
    public function getListAggregatedByOwner(array $opts = []): iterable
    {
        return $this->sums->getByOwner($opts);
    }

    /**
     * Gets synchroniser classes that should be run through.*
     * @param array $opts - opts object - if singleTask is provided and matches a known synchroniser class,
     * will exclusively return that class in an array.
     * @return array - array of synchroniser classes.
     */
    private function getSynchroniserClasses(array $opts): array
    {
        if ($opts['singleTask']) {
            return array_filter(
                Manager::SYNCHRONISERS,
                function ($synchroniserClass) use ($opts) {
                    return end(explode('\\', $synchroniserClass)) === $opts['singleTask'];
                }
            );
        }
        return  Manager::SYNCHRONISERS;
    }
}
