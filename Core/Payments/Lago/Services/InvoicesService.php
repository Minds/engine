<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Services;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Lago\Clients\InvoicesClient;
use Minds\Core\Payments\Lago\Enums\InvoicePaymentStatusEnum;
use Minds\Core\Payments\Lago\Enums\InvoiceStatusEnum;

class InvoicesService
{
    public function __construct(
        private readonly InvoicesClient $invoicesClient,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param int|null $userGuid
     * @param int|null $issuingDateFrom
     * @param int|null $issuingDateTo
     * @param InvoiceStatusEnum|null $invoiceStatus
     * @param InvoicePaymentStatusEnum|null $invoicePaymentStatus
     * @return array
     * @throws GuzzleException
     */
    public function getInvoices(
        int $page = 1,
        int $perPage = 12,
        ?int $userGuid = null,
        ?int $issuingDateFrom = null,
        ?int $issuingDateTo = null,
        ?InvoiceStatusEnum $invoiceStatus = null,
        ?InvoicePaymentStatusEnum $invoicePaymentStatus = null,
    ): array {
        return iterator_to_array(
            iterator: $this->invoicesClient->getInvoices(
                page: $page,
                perPage: $perPage,
                userGuid: $userGuid,
                issuingDateFrom: $issuingDateFrom,
                issuingDateTo: $issuingDateTo,
                invoiceStatus: $invoiceStatus,
                invoicePaymentStatus: $invoicePaymentStatus,
            )
        );
    }

    /**
     * @param string $invoiceId
     * @return string
     * @throws GuzzleException
     */
    public function fetchInvoicePDF(string $invoiceId): string
    {
        return $this->invoicesClient->downloadInvoicePDF($invoiceId);
    }
}
