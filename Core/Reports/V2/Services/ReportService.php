<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Services;

use Minds\Core\Config\Config;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Log\Logger;
use Minds\Core\Reports\Enums\Reasons\Illegal\SubReasonEnum as IllegalSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Nsfw\SubReasonEnum as NsfwSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Security\SubReasonEnum as SecuritySubReasonEnum;
use Minds\Core\Reports\Enums\ReportActionEnum;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use Minds\Core\Reports\Enums\ReportStatusEnum;
use Minds\Core\Reports\V2\Repositories\ReportRepository;
use Minds\Core\Reports\V2\Types\Report;
use Minds\Core\Reports\V2\Types\ReportEdge;
use Minds\Core\Reports\V2\Types\ReportsConnection;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Service for retrieval and handling of v2 reports.
 */
class ReportService
{
    public function __construct(
        private readonly ReportRepository $repository,
        private readonly ActionService $actionService,
        private readonly Config $config,
        private readonly Logger $logger
    ) {
    }

    /**
     * Get a single report.
     * @param int $reportGuid - guid of the report.
     * @param ReportStatusEnum|null $status - status of the report. Allows checking whether a report is in a given status.
     * @return Report|null - the report.
     */
    public function getReport(
        int $reportGuid,
        ReportStatusEnum $status = null
    ): ?Report {
        $tenantId = $this->getTenantId();

        return $this->repository->getReport(
            tenantId: $tenantId,
            reportGuid: $reportGuid,
            status: $status
        );
    }

    /**
     * Gets a list of reports as a Connection object.
     * @param int $limit - limit of reports to load.
     * @param int|null $loadAfter - load after cursor.
     * @param ReportStatusEnum|null $status - status of reports to load.
     * @return ReportsConnection - Reports connection.
     */
    public function getReports(
        int $limit = 12,
        ?int $loadAfter = null,
        ?ReportStatusEnum $status = ReportStatusEnum::PENDING
    ): ReportsConnection {
        $tenantId = $this->getTenantId();
        $hasMore = false;
        $initialLoadAfter = $loadAfter;

        $reports = $this->repository->getReports(
            tenantId: $tenantId,
            status: $status,
            limit: $limit,
            loadAfter: $loadAfter,
            hasMore: $hasMore
        );

        $edges = $this->buildEdges($reports, (string) $loadAfter);

        return (new ReportsConnection())
            ->setEdges($edges)
            ->setPageInfo(new PageInfo(
                hasNextPage: $hasMore,
                hasPreviousPage: false, // not supported.
                startCursor: (string) $initialLoadAfter,
                endCursor: (string) $loadAfter,
            ));

    }

    /**
     * Create a new report.
     * @param string $entityUrn - urn of the entity to report.
     * @param int $reportedByGuid - guid of the user reporting the entity.
     * @param ReportReasonEnum $reason - reason for the report.
     * @param IllegalSubReasonEnum|NsfwSubReasonEnum|SecuritySubReasonEnum|null $subReason - sub reason for the report.
     * @return bool
     */
    public function createNewReport(
        string $entityUrn,
        int $reportedByGuid,
        ReportReasonEnum $reason,
        IllegalSubReasonEnum|NsfwSubReasonEnum|SecuritySubReasonEnum|null $subReason = null,
    ): bool {
        $tenantId = $this->getTenantId();
        return $this->repository->createNewReport(
            tenantId: $tenantId,
            entityGuid: $this->getEntityGuidFromUrn($entityUrn),
            entityUrn: $entityUrn,
            reportedByGuid: $reportedByGuid,
            reason: $reason,
            subReason: $subReason,
        );
    }

    /**
     * Provide a verdict for a report.
     * @param int $reportGuid - guid of the report.
     * @param int $moderatedByGuid - guid of the user is moderating the report.
     * @param ReportActionEnum $action - action to take on the report.
     * @param User $loggedInUser
     * @return bool true on success.
     * @throws GraphQLException
     */
    public function provideVerdict(
        int $reportGuid,
        int $moderatedByGuid,
        ReportActionEnum $action,
        User $moderator
    ): bool {
        $tenantId = $this->getTenantId();
        $report = $this->getReport($reportGuid, ReportStatusEnum::PENDING);

        if (!$report) {
            throw new GraphQLException('No report found in pending state. Has it already been moderated?');
        }

        try {
            $this->actionService->handleReport(
                report: $report,
                action: $action,
                moderator: $moderator
            );
        } catch(NotFoundException $e) {
            // if the entity cannot be handled, ignore the report.
            $action = ReportActionEnum::IGNORE;
        }

        return $this->repository->updateWithVerdict(
            tenantId: $tenantId,
            entityGuid: $report->entityGuid,
            moderatedByGuid: $moderatedByGuid,
            action: $action,
            reason: $report->reason,
            subReason: $report->getSubReason()
        );
    }

    /**
     * Gets the tenant id from the config.
     * @return int - tenant id.
     */
    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?? throw new GraphQLException(
            'Only tenant networks can interact with V2 reports.'
        );
    }

    /**
     * Builds edges for connection from generator of reports.
     * @param iterable $reports - generator of reports.
     * @param string $cursor - cursor used to load these reports.
     * @return ReportEdge[] - array of edges.
     */
    private function buildEdges(iterable $reports, string $cursor = ''): array
    {
        $edges = [];
        foreach ($reports as $report) {
            $report->cursor = $cursor;
            $edges[] = new ReportEdge(
                $report,
                $cursor
            );
        }
        return $edges;
    }

    /**
     * Gets the entity guid from an entity urn.
     * @param string $entityUrn - urn of the entity.
     * @return int|null - guid of the entity.
     */
    private function getEntityGuidFromUrn(string $entityUrn): ?int
    {
        $urnSegments = explode(':', $entityUrn);
        return (int) end($urnSegments) ?? null;
    }
}
