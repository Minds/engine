<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Summaries;

use DateTime;
use DateTimeImmutable;
use Minds\Common\Urn;
use Minds\Core\Boost\V3\Common\ViewsScroller;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use SplObjectStorage;
use stdClass;

class Manager
{
    /** @var array */
    protected $buffer = [];

    public function __construct(
        private ?Repository $repository = null,
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
    }

    /**
     * Increment clicks for a boost summary, for a given Boost and date.
     * @param Boost $boost - given boost.
     * @param DateTime $date - date to update for.
     * @return bool - true on success.
     */
    public function incrementClicks(Boost $boost, DateTime $date): bool
    {
        return $this->repository->incrementClicks($boost->getGuid(), $date);
    }

    /**
     * Increments views for boost summary
     */
    public function incrementViews(int $tenantId, int $boostGuid, int $unixTimestamp): bool
    {
        $unixDate = (new DateTimeImmutable())->setTimestamp($unixTimestamp)->modify('midnight')->getTimestamp();

        if (!isset($this->buffer[$unixDate])) {
            $this->buffer[$unixDate] = [];
        }

        if (!isset($this->buffer[$unixDate][$boostGuid])) {
            $this->buffer[$unixDate][$boostGuid] = (object) [
                'tenantId' => $tenantId,
                'views' => 0,
            ];
        }

        ++$this->buffer[$unixDate][$boostGuid]->views;

        return true;
    }

    /**
     * @return void
     */
    public function flush(): void
    {
        $this->repository->beginTransaction();
        foreach ($this->buffer as $unixDate => $boostViews) {
            foreach ($boostViews as $guid => $data) {
                $this->repository->incrementViews($data->tenantId, $guid, (new DateTime())->setTimestamp($unixDate), $data->views);
            }
        }
        // Reset the buffer
        $this->buffer = [];
        // Commit to the database
        $this->repository->commitTransaction();
    }

}
