<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Clients;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Payments\Lago\Enums\InvoicePaymentStatusEnum;
use Minds\Core\Payments\Lago\Enums\InvoiceStatusEnum;
use Minds\Core\Payments\Lago\Enums\InvoiceTypeEnum;
use Minds\Core\Payments\Lago\Types\Customer;
use Minds\Core\Payments\Lago\Types\Invoice;

class InvoicesClient extends ApiClient
{
    public function __construct(
        HttpClient $httpClient
    ) {
        parent::__construct($httpClient);
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param int|null $userGuid
     * @param int|null $issuingDateFrom
     * @param int|null $issuingDateTo
     * @param InvoiceStatusEnum|null $invoiceStatus
     * @param InvoicePaymentStatusEnum|null $invoicePaymentStatus
     * @return iterable
     * @throws GuzzleException
     * @throws Exception
     */
    public function getInvoices(
        int $page = 1,
        int $perPage = 12,
        ?int $userGuid = null,
        ?int $issuingDateFrom = null,
        ?int $issuingDateTo = null,
        ?InvoiceStatusEnum $invoiceStatus = null,
        ?InvoicePaymentStatusEnum $invoicePaymentStatus = null,
    ): iterable {
        $params = [
            'page' => $page,
            'per_page' => $perPage,
        ];

        if ($userGuid) {
            $params['external_customer_id'] = $userGuid;
        }
        if ($issuingDateFrom > $issuingDateTo) {
            throw new Exception("Issuing date \"from\" cannot be greater than issuing date \"to\"");
        }
        if ($issuingDateFrom) {
            $params['issuing_date_from'] = date("Y-m-d", $issuingDateFrom);
        }
        if ($issuingDateTo) {
            $params['issuing_date_to'] = date("Y-m-d", $issuingDateTo);
        }
        if ($invoiceStatus) {
            $params['status'] = $invoiceStatus->value;
        }
        if ($invoicePaymentStatus) {
            $params['payment_status'] = $invoicePaymentStatus->value;
        }

        $response = $this->httpClient->get(
            uri: '/api/v1/invoices',
            options: [
                'query' => $params
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to get invoices");
        }

        $payload = json_decode($response->getBody()->getContents());

        foreach ($payload->invoices as $invoice) {
            yield new Invoice(
                lagoId: $invoice->lago_id,
                lagoInvoiceSequentialId: $invoice->sequential_id,
                lagoInvoiceNumber: $invoice->number,
                lagoInvoiceUrl: $invoice->file_url ?? "",
                issuingDate: strtotime($invoice->issuing_date),
                invoiceType: InvoiceTypeEnum::tryFrom($invoice->invoice_type),
                invoiceStatus: InvoiceStatusEnum::tryFrom($invoice->status),
                invoicePaymentStatus: InvoicePaymentStatusEnum::tryFrom($invoice->payment_status),
                totalAmountInCents: $invoice->total_amount_cents,
                customer: new Customer(
                    userGuid: (int) $invoice->customer->external_id,
                    lagoCustomerId: $invoice->customer->lago_id,
                    name: $invoice->customer->name,
                    createdAt: strtotime($invoice->customer->created_at),
                )
            );
        }
    }

    /**
     * @param string $invoiceId
     * @return string
     * @throws GuzzleException
     * @throws Exception
     */
    public function downloadInvoicePDF(string $invoiceId): string
    {
        $response = $this->httpClient->post(
            uri: "/api/v1/invoices/$invoiceId/download",
        );

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to get invoices");
        }

        $payload = json_decode($response->getBody()->getContents());
        $invoice = $payload->invoice;

        return $invoice->file_url ?? "";
    }
}
