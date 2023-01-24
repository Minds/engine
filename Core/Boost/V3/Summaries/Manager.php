<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Summaries;

use Cassandra\Timeuuid;
use DateTime;
use Minds\Common\Urn;
use Minds\Core\Boost\V3\Common\ViewsScroller;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;

class Manager
{
    const MAX_BUFFER = 1000;

    protected DateTime $date;

    /** @var int[] */
    protected $boostViewsBuffer = [];

    public function __construct(
        private ?ViewsScroller $viewsScroller = null,
        private ?Repository $repository = null,
        private ?Resolver $entitiesResolver = null,
    ) {
        $this->viewsScroller ??= Di::_()->get(ViewsScroller::class);
        $this->repository ??= Di::_()->get(Repository::class);
        $this->entitiesResolver ??= new Resolver();
    }

    /**
     * Runs through views and
     * @param DateTime $date
     * @return void
     */
    public function sync(DateTime $date): void
    {
        $this->date = $date;

        foreach ($this->viewsScroller->scroll(
            gtTimeuuid: new Timeuuid($this->date->getTimestamp() * 1000),
            ltTimeuuid: new Timeuuid((clone $this->date)->modify("+1 day")->getTimestamp() * 1000)
        ) as $row) {
            $campaign = $row['campaign'];

            $boost = $this->getBoostByCampaign($campaign);

            if (!$boost) {
                continue;
            }

            $this->incrementViews($boost);
        }

        // Save these to the database
        $this->saveToDb();
    }

    /**
     * @param Boost $boost
     * @return void
     */
    protected function incrementViews(Boost $boost): void
    {
        if (!isset($this->boostViewsBuffer[$boost->getGuid()])) {
            $this->boostViewsBuffer[$boost->getGuid()] = 0;
        }

        ++$this->boostViewsBuffer[$boost->getGuid()];
    }

    /**
     * @return void
     */
    protected function saveToDb(): void
    {
        foreach ($this->boostViewsBuffer as $guid => $views) {
            $this->repository->add((string) $guid, $this->date, $views);
        }
    }


    /**
     * Will return a boost from its campaign id
     * @param string $campaign
     * @return null|Boost
     */
    protected function getBoostByCampaign(string $campaign): ?Boost
    {
        if (strpos($campaign, 'urn:boost:', 0) === false) {
            return null;
        }

        $boost = $this->entitiesResolver->single(new Urn($campaign));

        if ($boost instanceof Boost) {
            return $boost;
        }

        return null;
    }
}
