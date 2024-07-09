<?php
namespace Minds\Core\Boost\V3\Ranking;

use Cassandra\Timeuuid;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;

class Manager
{
    const TIME_WINDOW_SECS = 3600; // 1 hour

    /** @var int[] - the total views for the time window */
    protected array $totalViews = [
        BoostTargetLocation::NEWSFEED . '_' . BoostTargetAudiences::CONTROVERSIAL => 0,
        BoostTargetLocation::NEWSFEED . '_' . BoostTargetAudiences::SAFE => 0,
        BoostTargetLocation::SIDEBAR . '_' . BoostTargetAudiences::CONTROVERSIAL => 0,
        BoostTargetLocation::SIDEBAR . '_' . BoostTargetAudiences::SAFE => 0,
    ];

    /** @var int[] - the views for the timewindow, split by key value boostGuid=>totalViews */
    protected array $viewsByBoostGuid = [];

    /** @var Timeuuid|null - the uuid of the oldest view record we hold */
    protected ?Timeuuid $minScanUuid = null;

    /** @var Timeuuid|null - the uuid of the newest view record we hold */
    protected ?Timeuuid $maxScanUuid = null;

    /** @var BoostShareRatio[] */
    protected array $activeBoostsCache = [];

    /** @var bool - if true then we will not save to the database */
    protected $dryRun = false;

    public function __construct(
        protected ?Repository $repository = null,
        protected ?Scroll $scroll = null,
        protected ?Logger $logger = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->scroll ??= Di::_()->get('Database\Cassandra\Cql\Scroll');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Set to true if you don't want to save to the database
     * @param bool $dryRun
     * @return self
     */
    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;
        return $this;
    }

    /**
     * Set the base timestamp. Ideal for replaying ranking flow alongside `setDryRun(true)`
     * @param int $unixTs
     * @return self
     */
    public function setFromUnixTs(int $unixTs): self
    {
        $this->minScanUuid = new Timeuuid(($unixTs - self::TIME_WINDOW_SECS) * 1000);
        $this->maxScanUuid = $this->minScanUuid;
        return $this;
    }

    /**
     * Call this function periodically to update the rankings of the boosts
     * This function will also store the boosts to the ranking table
     * @throws ServerErrorException
     */
    public function calculateRanks(): void
    {
        /**
         * Grab all the boosts are scheduled to be delivered
         */
        foreach ($this->repository->getBoostShareRatios() as $boostShareRatio) {
            $this->activeBoostsCache[$boostShareRatio->guid] = $boostShareRatio;
        }

        /**
         * Update our view memory
         */
        $this->collectAllViews();

        /**
         * Loop through all of our active boosts(that we just built above) and collect their bid ration
         */

        $this->repository->beginTransaction();
        foreach ($this->activeBoostsCache as $boost) {
            $targetAudiences = [
                BoostTargetAudiences::CONTROVERSIAL => $boost->getTargetAudienceShare(BoostTargetAudiences::CONTROVERSIAL), // Will always go to open audience
                BoostTargetAudiences::SAFE => $boost->getTargetAudienceShare(BoostTargetAudiences::SAFE), // Only safe boosts will go here
            ];

            $ranking = new BoostRanking($boost->tenantId, $boost->guid);
        
            foreach ($targetAudiences as $targetAudience => $shareOfBids) {
                // This is our ideal target
                $targetKey = $boost->getTargetLocation() . '_' . $targetAudience;
                $totalViews = $this->totalViews[$targetKey];
                $viewsTarget = $totalViews * $shareOfBids; // ie. 250

                // What we've actual had in the time window
                $viewsActual =  $this->viewsByBoostGuid[$boost->guid] ?? 0; // ie. 125

                // Work out the rank
                $rank = $viewsTarget / max($viewsActual, 1);

                $ranking->setRank($targetAudience, $rank);

                $this->logger->info("Setting {$boost->guid} rank to $rank", [
                    'totalViews' => $totalViews,
                    'target' => $viewsTarget,
                    'actual' => $viewsActual,
                    'share' => $shareOfBids,
                ]);
            }

            if (!$this->dryRun) {
                $this->repository->addBoostRanking($ranking);
            }
        }
        $this->repository->commitTransaction();
    }

    /**
     * Collects all the view data and stores them in memory
     * Each time this function is called it will resume from the last position
     * @return void
     * @throws ServerErrorException
     */
    public function collectAllViews(): void
    {
        /**
         * If there is no min value set, set to TIME_WINDOW_SECS ago (ie. 1 hour ago)
         */
        if (!$this->minScanUuid) {
            $this->minScanUuid = new Timeuuid((time() - self::TIME_WINDOW_SECS) * 1000);
        }
        if (!$this->maxScanUuid) {
            $this->maxScanUuid = $this->minScanUuid;
        }
       
        /**
         * Scan for views since the last scan position
         */
        $query = $this->prepareQuery(
            gtTimeuuid: $this->maxScanUuid, // Scan for views greater than our last run
        );
        foreach ($this->scroll->request($query) as $row) {
            // Set the maxScanUuid to be the last item we see, as we will query from here on next run
            $this->maxScanUuid = $row['uuid'];

            $campaign = $row['campaign'];

            if ($campaign && $boost = $this->getBoostByCampaign($campaign)) {
                $this->updateViews($boost, val: 1); // Increment in-memory views
            }
        }

        /**
         * Prune views outside of the valid time window
         */
        $query = $this->prepareQuery(
            gtTimeuuid: $this->minScanUuid,                                    // find those less than one hour
            ltTimeuuid: new Timeuuid((time() - self::TIME_WINDOW_SECS) * 1000) // but greater than our last min scan
        );
        foreach ($this->scroll->request($query) as $row) {
            // Set the minScanUuid to be the last item we see, as the next prune will look for GreaterThan this uuid
            $this->minScanUuid = $row['uuid'];

            $campaign = $row['campaign'];

            if ($campaign && $boost = $this->getBoostByCampaign($campaign)) {
                $this->updateViews($boost, val: -1); // Decrement in-memory views
            }
        }
    }

    /**
     * Prepares the query for our scans
     * TODO: support for overlapping partitions. ie. midnight should include previous day partition
     * @param null|Timeuuid $gtTimeuuid
     * @param null|Timeuuid $ltTimeuuid
     * @return Custom
     * @throws ServerErrorException
     */
    protected function prepareQuery(
        \Cassandra\Timeuuid $gtTimeuuid = null,
        \Cassandra\Timeuuid $ltTimeuuid = null
    ): Prepared\Custom {
        $statement = "SELECT * FROM views WHERE ";
        $values = [];

        if (!($gtTimeuuid || $ltTimeuuid)) {
            throw new ServerErrorException("You must provide at least one timeuuid");
        }

        $dateTime = $gtTimeuuid ? $gtTimeuuid->toDateTime() : $ltTimeuuid->toDateTime();
    
        // Year implode(', ', array_fill(0, count($years), '?'));
        
        $statement .= 'year=? ';
        $values[] = (int) $dateTime->format('Y');

        /**
         * Months
         * If we are on the last day of the month, include the next month
         */
        $months = [
            new \Cassandra\Tinyint((int) $dateTime->format('m')),
        ];
        if ((int) $dateTime->format('t') === (int) $dateTime->format('d')) { // 't' = num days in month
            $months[] = new \Cassandra\Tinyint((int) (clone $dateTime)->modify('+1 day')->format('m'));
        }
        $statement .= 'AND month IN (' . implode(', ', array_fill(0, count($months), '?')) . ') ';
        $values = [...$values, ...$months];

        /**
         * Day
         * If we are at 11pm, include tomorrow too
         */
        $days = [
            new \Cassandra\Tinyint((int) $dateTime->format('d')),
        ];
        if ((int) $dateTime->format('H') >= 23) {
            $days[] = new \Cassandra\Tinyint((int) (clone $dateTime)->modify('+1 hour')->format('d'));
        }
        $statement .= 'AND day IN (' . implode(', ', array_fill(0, count($days), '?')) . ') ';
        $values = [...$values, ...$days];
        
        // Timeuuid

        if ($gtTimeuuid) {
            $statement .= 'AND uuid>? ';
            $values[] = $gtTimeuuid;
        }
        if ($ltTimeuuid) {
            $statement .= 'AND uuid<? ';
            $values[] = $ltTimeuuid;
        }

        $statement .= "ORDER BY month,day,uuid ASC";

        $query = new Prepared\Custom();
        $query->query($statement, $values);

        $query->setOpts([
            'page_size' => 2500,
            'consistency' => \Cassandra::CONSISTENCY_ONE,
        ]);

        return $query;
    }

    /**
     * Safe boosts will go to Open targets.
     * Open boots will ONLY go to Open targets, never safe.
     * @param BoostShareRatio $boost
     * @param int $val - negative to decrement
     * @return void
     */
    protected function updateViews(BoostShareRatio $boost, int $val = 1): void
    {
        $targetLocation = $boost->getTargetLocation();

        $this->totalViews[$targetLocation . '_' . BoostTargetAudiences::CONTROVERSIAL] += $val;

        if ($boost->isSafe()) {
            $this->totalViews[$targetLocation . '_' . BoostTargetAudiences::SAFE] += $val;
        }

        if (!isset($this->viewsByBoostGuid[$boost->guid])) {
            $this->viewsByBoostGuid[$boost->guid] = $val;
        } else {
            $this->viewsByBoostGuid[$boost->guid] += $val;
        }
    }

    /**
     * Will return a boost from its campaign id
     * @param string $campaign
     * @return null|BoostShareRatio
     */
    protected function getBoostByCampaign(string $campaign): ?BoostShareRatio
    {
        if (strpos($campaign, 'urn:boost:', 0) === false) {
            return null;
        }

        $guid = str_replace('urn:boost:', '', $campaign);

        if (!is_numeric($guid)) {
            return null; // Old style boost
        }

        return $this->activeBoostsCache[$guid] ?? $this->repository->getBoostShareRatiosByGuid($guid);
    }
}
