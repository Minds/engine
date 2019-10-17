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
        PartnerEarningsSynchroniser::class,
        SignupsSynchroniser::class,
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
     * @return iterable
     */
    public function sync(): iterable
    {
        foreach (Manager::SYNCHRONISERS as $synchroniserClass) {
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
    }

    /**
     * @param array $opts
     * @retun iterable
     */
    public function getListAggregatedByOwner(array $opts = []): iterable
    {
        return $this->sums->getByOwner($opts);
    }
}
