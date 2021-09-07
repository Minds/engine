<?php
namespace Minds\Core\Analytics\EntityCentric;

use Minds\Core\Monetization\Partners\Manager as PartnersManager;
use DateTime;
use Exception;

class PartnerEarningsSynchroniser
{
    /** @var PartnersManager */
    private $partnersManager;

    /** @var int */
    protected $from;

    public function __construct($partnersManager = null)
    {
        $this->partnersManager = $partnersManager ?? new PartnersManager;
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
        $opts = [];
        $opts['from'] = $this->from;

        $records = [];

        $i = 0;
        while (true) {
            $result = $this->partnersManager->getList($opts);

            $opts['offset'] = $result->getPagingToken();

            foreach ($result as $deposit) {
                $urn = "urn:user:{$deposit->getUserGuid()}";
                $record = new EntityCentricRecord();
                $record->setEntityUrn($urn)
                    ->setOwnerGuid($deposit->getUserGuid())
                    ->setTimestamp($deposit->getTimestamp()) // TODO: confirm if this should be rounded to midnight
                    ->setResolution('day');
                // In order to increment sums, replace with what has already been seen
                if (isset($records[$record->getUrn()])) {
                    $record = $records[$record->getUrn()];
                }
                $record->incrementSum('usd_earnings::total', $deposit->getAmountCents());
                $record->incrementSum("usd_earnings::{$deposit->getItem()}", $deposit->getAmountCents());
                $records[$record->getUrn()] = $record;
            }

            if ($result->isLastPage()) {
                break;
            }
        }

        foreach ($records as $record) {
            var_dump($record);
            yield $record;
        }
    }
}
