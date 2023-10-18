<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Types;

use Minds\Core\Payments\Lago\Enums\InvoicePaymentStatusEnum;
use Minds\Core\Payments\Lago\Enums\InvoiceStatusEnum;
use Minds\Core\Payments\Lago\Enums\InvoiceTypeEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Invoice
{
    public function __construct(
        #[Field] public readonly string $lagoId,
        #[Field] public readonly int $lagoInvoiceSequentialId,
        #[Field] public readonly string $lagoInvoiceNumber,
        #[Field] public readonly string $lagoInvoiceUrl,
        #[Field] public readonly int $issuingDate,
        #[Field] public readonly InvoiceTypeEnum $invoiceType,
        #[Field] public readonly InvoiceStatusEnum $invoiceStatus,
        #[Field] public readonly InvoicePaymentStatusEnum $invoicePaymentStatus,
        #[Field] public readonly int $totalAmountInCents,
        #[Field] public readonly Customer $customer,
    ) {
    }
}
