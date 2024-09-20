<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Core\MultiTenant\Bootstrap\Models\BootstrapStepProgress;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\RawExp;

/**
 * Repository for the Bootstrapping process progress state.
 */
class BootstrapProgressRepository extends AbstractRepository
{
    /**
     * Get progress for a specific tenant
     * @param int $tenantId - The ID of the tenant to get progress for.
     * @return array - An array of BootstrapStepProgress models.
     * @throws ServerErrorException
     */
    public function getProgress(int $tenantId = null): array
    {
        if (!$tenantId) {
            $tenantId = $this->config->get('tenant_id') ?? null;

            if (!$tenantId) {
                throw new ServerErrorException('Progress can only be retrieved for a tenant');
            }
        }

        $stmt = $this->mysqlClientReaderHandler->select()
            ->from('minds_tenant_bootstrap_progress')
            ->where('tenant_id', '=', new RawExp(':tenant_id'))
            ->prepare();

        try {
            $stmt->execute(['tenant_id' => $tenantId]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            usort($rows, function ($a, $b) {
                return strtotime($a['last_run_timestamp']) - strtotime($b['last_run_timestamp']);
            });

            return array_map(function ($row) {
                return new BootstrapStepProgress(
                    tenantId: (int) $row['tenant_id'],
                    step: BootstrapStepEnum::tryFrom($row['step_name']) ?? null,
                    success: (bool) $row['success'],
                    lastRunTimestamp: new \DateTime($row['last_run_timestamp'])
                );
            }, $rows);
        } catch (PDOException $e) {
            $this->logger->error($e);
            throw new ServerErrorException('Failed to get bootstrap progress', 0, $e);
        }
    }

    /**
     * Update progress for a bootstrap step.
     * @param string $stepName - The name of the step to update.
     * @param bool $success - Whether the step was successful.
     * @return bool - Whether the update was successful.
     * @throws ServerErrorException
     */
    public function updateProgress(BootstrapStepEnum $step, bool $success = true, int $tenantId = null): bool
    {
        if (!$tenantId) {
            $tenantId = $this->config->get('tenant_id') ?? null;

            if (!$tenantId) {
                throw new ServerErrorException('Progress can only be retrieved for a tenant');
            }
        }

        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into('minds_tenant_bootstrap_progress')
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'step_name' => new RawExp(':step_name'),
                'success' => new RawExp(':success'),
                'last_run_timestamp' => new RawExp('CURRENT_TIMESTAMP'),
            ])
            ->onDuplicateKeyUpdate([
                'success' => new RawExp(':success'),
                'last_run_timestamp' => new RawExp('CURRENT_TIMESTAMP'),
            ])
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $tenantId,
                'step_name' => $step->value,
                'success' => $success ? 1 : 0,
            ]);
        } catch (PDOException $e) {
            $this->logger->error($e);
            throw new ServerErrorException('Failed to update bootstrap progress', 0, $e);
        }
    }
}
