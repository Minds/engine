<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;

/**
 * Service for managing the bootstrap progress.
 */
class BootstrapProgressService
{
    public function __construct(
        private BootstrapProgressRepository $progressRepository,
        private Logger $logger
    ) {
    }

    /**
     * Get progress for all bootstrap steps.
     * @param int|null $tenantId - The ID of the tenant to get progress for.
     * @return array - An array of BootstrapStepProgress models.
     */
    public function getProgress(int $tenantId = null): array
    {
        try {
            return $this->progressRepository->getProgress(tenantId: $tenantId);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get bootstrap progress: ' . $e->getMessage());
            return [];
        }
    }
}
