<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\PreApproval;

use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Repository;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;

/**
 * Manager to determine whether a given boost should be pre-approved.
 */
class Manager
{
    public function __construct(
        private ?Repository $repository = null,
        private ?ExperimentsManager $experimentsManager = null,
        private ?Config $config = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->experimentsManager ??= Di::_()->get('Experiments\Manager');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Whether boost should be pre-approved for given user.
     * @param User $user - given user.
     * @return boolean true if boost should be pre-approved.
     */
    public function shouldPreApprove(User $user): bool
    {
        // Tenant networks should not have preapproved boosts.
        if ((bool) $this->config->get('tenant_id')) {
            return false;
        }

        if (!$this->isExperimentActive()) {
            return false;
        }

        $limit = $this->getApprovedThreshold();
        $statusCounts = $this->getBoostStatusCounts($user, $limit);

        $totalCount = array_sum(array_values(
            $statusCounts
        ));

        return $totalCount >= $limit &&
            !isset($statusCounts[BoostStatus::REPORTED]) &&
            !isset($statusCounts[BoostStatus::REJECTED]);
    }

    /**
     * Get boost status counts.
     * @param User $user - user to get counts for.
     * @param integer $limit - count X last boosts.
     * @return array - `status => count` array.
     */
    private function getBoostStatusCounts(User $user, int $limit = 10): array
    {
        return $this->repository->getBoostStatusCounts(
            limit: $limit,
            targetUserGuid: $user->getGuid(),
            statuses: [
                BoostStatus::APPROVED,
                BoostStatus::COMPLETED,
                BoostStatus::REJECTED,
                BoostStatus::REPORTED
            ]
        );
    }

    /**
     * Get approval threshold - a user must have this number of boosts in a row that have not been
     * rejected or reported to be auto-approved.
     * @return int approval threshold.
     */
    private function getApprovedThreshold(): int
    {
        return $this->config->get('boost')['pre_approval_threshold'] ?? 10;
    }

    /**
     * Whether experiment is active.
     * @return bool true if experiment is active.
     */
    private function isExperimentActive(): bool
    {
        return $this->experimentsManager->isOn('front-5882-boost-preapprovals');
    }
}
