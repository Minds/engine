<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Types;

use Minds\Core\Payments\Lago\Enums\PaymentProviderEnum;
use Minds\Core\Payments\Lago\Enums\SubscriptionBillingTimeEnum;
use Minds\Core\Payments\Lago\Enums\SubscriptionStatusEnum;
use TheCodingMachine\GraphQLite\Annotations\Factory;

class InputTypesFactory
{
    /**
     * @param int $mindsGuid
     * @param string $name
     * @param BillingConfiguration|null $billingConfiguration
     * @param string|null $email
     * @return Customer
     */
    #[Factory(name: "CustomerInput", default: true)]
    public function createCustomer(
        int $mindsGuid,
        string $name,
        ?BillingConfiguration $billingConfiguration = null,
        ?string $email = null,
    ): Customer {
        return new Customer(
            mindsGuid: $mindsGuid,
            lagoCustomerId: "",
            name: $name,
            createdAt: time(),
            billingConfiguration: $billingConfiguration,
            email: $email,
        );
    }

    /**
     * @param PaymentProviderEnum $paymentProvider
     * @param int|null $invoiceGracePeriod
     * @param string|null $providerCustomerId
     * @param bool $sync
     * @param bool $syncWithProvider
     * @param string[]|null $providerPaymentMethods
     * @return BillingConfiguration
     */
    #[Factory(name: "BillingConfigurationInput", default: true)]
    public function createBillingConfiguration(
        PaymentProviderEnum $paymentProvider,
        ?int $invoiceGracePeriod,
        ?string $providerCustomerId = null,
        bool $sync = false,
        bool $syncWithProvider = false,
        ?array $providerPaymentMethods = null,
    ): BillingConfiguration {
        return new BillingConfiguration(
            paymentProvider: $paymentProvider,
            invoiceGracePeriod: $invoiceGracePeriod,
            providerCustomerId: $providerCustomerId,
            sync: $sync,
            syncWithProvider: $syncWithProvider,
            providerPaymentMethods: $providerPaymentMethods,
        );
    }

    #[Factory(name: "SubscriptionInput", default: true)]
    public function createSubscription(
        SubscriptionBillingTimeEnum $billingTime,
        int $mindsCustomerId,
        string $mindsSubscriptionId,
        string $planCodeId,
        string $name = "",
    ): Subscription {
        return new Subscription(
            billingTime: $billingTime,
            mindsCustomerId: $mindsCustomerId,
            mindsSubscriptionId: $mindsSubscriptionId,
            planCodeId: $planCodeId,
            status: SubscriptionStatusEnum::ACTIVE,
            name: $name,
        );
    }
}
