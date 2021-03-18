<?php
namespace Minds\Core\Analytics\EntityCentric;

use Minds\Core\Analytics\Metrics\Signup;
use DateTime;
use Exception;

class SignupsSynchroniser
{
    /** @var array */
    private $records = [];

    /** @var Signup */
    private $signupMetric;

    /** @var int */
    protected $from;

    public function __construct($signupMetric = null)
    {
        $this->signupMetric = $signupMetric ?? new Signup;
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
     * Convert to records
     * @return iterable
     */
    public function toRecords(): iterable
    {
        $date = (new DateTime())->setTimestamp($this->from);
        $now = new DateTime();
        $days = (int) $date->diff($now)->format('%a');

        foreach ($this->signupMetric->get($days) as $bucket) {
            error_log("Signups (total {$bucket['date']}) {$bucket['total']}");
            $record = new EntityCentricRecord();
            $record->setEntityUrn("urn:metric:global")
                ->setOwnerGuid((string) 0) // Site is owner
                ->setTimestamp($bucket['timestamp'])
                ->setResolution('day')
                ->incrementSum('signups::total', $bucket['total']);
            $this->records[] = $record;
        }
        
        foreach ($this->records as $record) {
            yield $record;
        }
    }
}
