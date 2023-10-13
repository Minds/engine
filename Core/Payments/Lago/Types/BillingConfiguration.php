<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Types;

use Minds\Core\Payments\Lago\Enums\PaymentProviderEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class BillingConfiguration
{
    /**
     * @param PaymentProviderEnum $paymentProvider
     * @param int|null $invoiceGracePeriod
     * @param string|null $providerCustomerId
     * @param bool $sync
     * @param bool $syncWithProvider
     * @param string[]|null $providerPaymentMethods
     */
    public function __construct(
        #[Field] public readonly PaymentProviderEnum $paymentProvider,
        #[Field] public readonly ?int $invoiceGracePeriod = null,
        #[Field] public readonly ?string $providerCustomerId = null,
        #[Field] public readonly bool $sync = false,
        #[Field] public readonly bool $syncWithProvider = false,
        public readonly ?array $providerPaymentMethods = null,
    ) {
    }

    /**
     * @return string[]|null
     */
    #[Field]
    public function getProviderPaymentMethods(): ?array
    {
        return $this->providerPaymentMethods;
    }
}
