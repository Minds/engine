<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Controllers;

use Minds\Core\Reports\V2\Services\ReportService;
use Minds\Core\Reports\V2\Types\VerdictInput;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;

/**
 * Controller for providing report verdicts.
 */
class VerdictController
{
    public function __construct(
        private readonly ReportService $service
    ) {
    }

    /**
     * Provide a verdict for a report.
     * @param VerdictInput $verdictInput - verdict input.
     * @return bool true on success.
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function provideVerdict(
        VerdictInput $verdictInput,
        #[InjectUser] User $loggedInUser // Do not add in docblock as it will break GraphQL
    ): bool {
        return $this->service->provideVerdict(
            reportGuid: $verdictInput->reportGuid,
            moderatedByGuid: (int) $loggedInUser->getGuid(),
            action: $verdictInput->action,
        );
    }
}
