<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Lago\Enums\InvoicePaymentStatusEnum;
use Minds\Core\Payments\Lago\Enums\InvoiceStatusEnum;
use Minds\Core\Payments\Lago\Services\InvoicesService;
use Minds\Core\Payments\Lago\Types\Invoice;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class InvoicesController
{
    public function __construct(
        private readonly InvoicesService $invoicesService,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param string|null $userGuid
     * @param int|null $issuingDateFrom
     * @param int|null $issuingDateTo
     * @param InvoiceStatusEnum|null $invoiceStatus
     * @param InvoicePaymentStatusEnum|null $invoicePaymentStatus
     * @return Invoice[]
     * @throws GuzzleException
     */
    #[Query]
    #[Logged]
    public function getInvoices(
        int $page = 1,
        int $perPage = 12,
        ?string $userGuid = null,
        ?int $issuingDateFrom = null,
        ?int $issuingDateTo = null,
        ?InvoiceStatusEnum $invoiceStatus = null,
        ?InvoicePaymentStatusEnum $invoicePaymentStatus = null,
    ): array {
        return $this->invoicesService->getInvoices(
            page: $page,
            perPage: $perPage,
            userGuid: (int) $userGuid,
            issuingDateFrom: $issuingDateFrom,
            issuingDateTo: $issuingDateTo,
            invoiceStatus: $invoiceStatus,
            invoicePaymentStatus: $invoicePaymentStatus,
        );
    }

    /**
     * @param string $invoiceId
     * @param User|null $loggedInUser
     * @return string
     * @throws GuzzleException
     */
    #[Query]
    #[Logged]
    #[Security('is_granted("ROLE_ADMIN", loggedInUser)')]
    public function getInvoicePDFUrl(
        string $invoiceId,
        #[InjectUser] User $loggedInUser = null,
    ): string {
        return $this->invoicesService->fetchInvoicePDF($invoiceId);
    }
}
