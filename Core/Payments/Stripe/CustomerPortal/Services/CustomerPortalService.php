<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\CustomerPortal\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\CustomerPortal\Enums\CustomerPortalSubscriptionCancellationModeEnum;
use Minds\Core\Payments\Stripe\CustomerPortal\Repositories\CustomerPortalConfigurationRepository;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Exceptions\ServerErrorException;
use Stripe\BillingPortal\Configuration as CustomerPortalConfiguration;
use Stripe\BillingPortal\Session as CustomerPortalSession;
use Stripe\Exception\ApiErrorException;

class CustomerPortalService
{
    public function __construct(
        private readonly StripeClient                          $stripeClient,
        private readonly CustomerPortalConfigurationRepository $customerPortalConfigurationRepository,
        private readonly Config                                $config
    ) {
    }

    /**
     * @param string $stripeCustomerId
     * @param string $redirectUrl
     * @param array|null $flowData
     * @return string
     * @throws ServerErrorException
     */
    public function createCustomerPortalSession(
        string $stripeCustomerId,
        string $redirectUrl,
        ?array $flowData = null
    ): string {

        $customerPortalConfigurationId = $this->customerPortalConfigurationRepository->getCustomerPortalConfigurationId();
        if (!$customerPortalConfigurationId) {
            $customerPortalConfigurationId = $this->createCustomerPortalDefaultConfiguration();
        }

        $customerPortalDetails = [
            'customer' => $stripeCustomerId,
            'configuration' => $customerPortalConfigurationId,
            'return_url' => $redirectUrl,
        ];

        if ($flowData) {
            $customerPortalDetails['flow_data'] = $flowData;
        }

        try {
            /**
             * @var CustomerPortalSession $customerPortalSession
             */
            $customerPortalSession = $this->stripeClient
                ->billingPortal
                ->sessions
                ->create($customerPortalDetails);

            return $customerPortalSession->url;
        } catch (ApiErrorException $e) {
            throw new ServerErrorException(message: 'Failed to create customer portal session', previous: $e);
        }
    }

    /**
     * @return string
     * @throws ServerErrorException
     */
    public function createCustomerPortalDefaultConfiguration(): string
    {
        try {
            /**
             * @var CustomerPortalConfiguration $customerPortalConfiguration
             */
            $customerPortalConfiguration = $this->stripeClient
                ->billingPortal
                ->configurations
                ->create([
                    'business_profile' => [
                        'headline' => 'Manage your subscription',
                    ],
                    'features' => [
                        'customer_update' => [
                            'allowed_updates' => ['email'],
                            'enabled' => true,
                        ],
                        'invoice_history' => [
                            'enabled' => false,
                        ],
                        'payment_method_update' => [
                            'enabled' => true,
                        ],
                        'subscription_cancel' => [
                            'enabled' => true,
                            'mode' => CustomerPortalSubscriptionCancellationModeEnum::AT_PERIOD_END->value
                        ],
                        'subscription_pause' => [
                            'enabled' => false,
                        ],
                    ],
                    'default_return_url' => $this->config->get('site_url'),
                ]);

            $this->customerPortalConfigurationRepository->storeCustomerPortalConfiguration(
                customerPortalConfigId: $customerPortalConfiguration->id
            );

            return $customerPortalConfiguration->id;
        } catch (ApiErrorException $e) {
            throw new ServerErrorException(message: 'Failed to create customer portal default configuration', previous: $e);
        }
    }
}
