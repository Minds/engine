<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Controllers;

use Minds\Core\Reports\Enums\ReportStatusEnum;
use Minds\Core\Reports\V2\Services\ReportService;
use Minds\Core\Reports\V2\Types\ReportInput;
use Minds\Core\Reports\V2\Types\ReportsConnection;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

/**
 * Controller for the handling of reports.
 */
class ReportController
{
    public function __construct(
        private readonly ReportService $service
    ) {
    }

    /**
     * Gets reports.
     * @param int $first - limit of reports to load.
     * @param int $after - load after cursor.
     * @param ReportStatusEnum status - status of reports to load.
     * @return ReportsConnection - reports connection.
     */
    #[Query]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function getReports(
        int $first = 12,
        int $after = null,
        ?ReportStatusEnum $status = ReportStatusEnum::PENDING,
        #[InjectUser] ?User $loggedInUser = null // Do not add in docblock as it will break GraphQL
    ): ReportsConnection {
        return $this->service->getReports(
            limit: $first,
            loadAfter: $after,
            status: $status
        );
    }

    /**
     * Create a new report.
     * @param ReportInput $reportInput - report input.
     * @return bool true on success.
     */
    #[Mutation]
    #[Logged]
    public function createNewReport(
        ReportInput $reportInput,
        #[InjectUser] User $loggedInUser // Do not add in docblock as it will break GraphQL
    ): bool {
        return $this->service->createNewReport(
            entityUrn: $reportInput->entityUrn,
            reason: $reportInput->reason,
            subReason: $reportInput->getSubReason(),
            reportedByGuid: (int) $loggedInUser->getGuid()
        );
    }
}
