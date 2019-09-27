<?php
namespace Minds\Core\Analytics\EntityCentric;

use Minds\Core\Analytics\Metrics\Active;
use DateTime;
use Exception;

class ActiveUsersSynchroniser
{
    /** @var array */
    private $records = [];

    /** @var Active */
    private $activeMetric;

    public function __construct($activeMetric = null)
    {
        $this->activeMetric = $activeMetric ?? new Active();
    }

    public function setFrom($from): self
    {
        $this->from = $from;
        return $this;
    }

    public function toRecords()
    {
        $date = (new DateTime())->setTimestamp($this->from);
        $now = new DateTime();
        $days = (int) $date->diff($now)->format('%a');
        $months = round($days / 28);

        // Daily resolution
        foreach ($this->activeMetric->get($days) as $bucket) {
            $record = new EntityCentricRecord();
            $record->setEntityUrn("urn:metric:global")
                ->setOwnerGuid((string) 0) // Site is owner
                ->setTimestamp($bucket['timestamp'])
                ->setResolution('day')
                ->incrementSum('active::total', $bucket['total']);
            $this->records[] = $record;
        }

        // Monthly resolution
        foreach ($this->activeMetric->get($months, 'month') as $bucket) {
            $record = new EntityCentricRecord();
            $record->setEntityUrn("urn:metric:global")
                ->setOwnerGuid((string) 0) // Site is owner
                ->setTimestamp($bucket['timestamp'])
                ->setResolution('month')
                ->incrementSum('active::total', $bucket['total']);
            $this->records[] = $record;
        }

        foreach ($this->records as $record) {
            yield $record;
        }
    }
}
