<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Partners;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;

class Manager
{
    public function __construct(
        private ?Repository $repository = null,
        private ?Logger $logger = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    public function beginTransaction(): void
    {
        $this->repository->beginTransaction();
    }

    public function rollbackTransaction(): void
    {
        $this->repository->rollbackTransaction();
    }

    /**
     * @param string $userGuid
     * @param string $boostGuid
     * @param int $eventTimestamp
     * @return bool
     */
    public function recordBoostPartnerView(
        string $userGuid,
        string $boostGuid,
        int $eventTimestamp
    ): bool {
        return $this->repository->add(
            userGuid: $userGuid,
            boostGuid: $boostGuid,
            lastViewedTimestamp: $eventTimestamp
        );
    }

    /**
     * @param int $fromTimestamp
     * @param int|null $toTimestamp
     * @return iterable
     */
    public function getRevenueDetails(int $fromTimestamp, ?int $toTimestamp = null): iterable
    {
        foreach ($this->repository->getCPMs($fromTimestamp, $toTimestamp) as $CPM) {
            yield $CPM;
        }
    }

    public function commitTransaction(): void
    {
        $this->repository->commitTransaction();
    }
}
