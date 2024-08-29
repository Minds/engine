<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\Stripe\CustomerPortal\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\CustomerPortal\Enums\CustomerPortalSubscriptionCancellationModeEnum;
use Minds\Core\Payments\Stripe\CustomerPortal\Repositories\CustomerPortalConfigurationRepository;
use Minds\Core\Payments\Stripe\CustomerPortal\Services\CustomerPortalService;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Payments\Stripe\StripeClient;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;
use Stripe\BillingPortal\Configuration as CustomerPortalConfiguration;
use Stripe\BillingPortal\Session as CustomerPortalSession;
use Stripe\Service\BillingPortal\ConfigurationService as CustomerPortalConfigurationService;
use Stripe\Service\BillingPortal\SessionService as CustomerPortalSessionService;

class CustomerPortalServiceSpec extends ObjectBehavior
{
    private Collaborator $stripeCustomerPortalSessionServiceMock;
    private Collaborator $stripeCustomerPortalConfigurationServiceMock;
    private Collaborator $customerPortalConfigurationRepositoryMock;
    private Collaborator $configMock;
    private Collaborator $stripeApiKeyConfigMock;

    private ReflectionClass $stripeClientMockFactory;
    private ReflectionClass $stripeBillingPortalSessionMockFactory;
    private ReflectionClass $stripeBillingPortalConfigurationMockFactory;

    public function let(
        CustomerPortalSessionService          $customerPortalSessionService,
        CustomerPortalConfigurationService    $customerPortalConfigurationService,
        CustomerPortalConfigurationRepository $customerPortalConfigurationRepository,
        Config                                $config,
        StripeApiKeyConfig                    $stripeApiKeyConfig,
    ): void {
        $this->stripeCustomerPortalSessionServiceMock = $customerPortalSessionService;
        $this->stripeCustomerPortalConfigurationServiceMock = $customerPortalConfigurationService;
        $this->customerPortalConfigurationRepositoryMock = $customerPortalConfigurationRepository;
        $this->configMock = $config;
        $this->stripeApiKeyConfigMock = $stripeApiKeyConfig;

        $this->stripeClientMockFactory = new ReflectionClass(StripeClient::class);
        $this->stripeBillingPortalSessionMockFactory = new ReflectionClass(CustomerPortalSession::class);
        $this->stripeBillingPortalConfigurationMockFactory = new ReflectionClass(CustomerPortalConfiguration::class);

        $this->beConstructedWith(
            $this->prepareStripeClientMock(),
            $this->customerPortalConfigurationRepositoryMock,
            $this->configMock,
            $stripeApiKeyConfig,
        );
    }

    private function prepareStripeClientMock(): StripeClient
    {
        $stripeClientMock = $this->stripeClientMockFactory->newInstanceWithoutConstructor();
        $this->stripeClientMockFactory->getProperty('coreServiceFactory')
            ->setValue($stripeClientMock, new class($this->stripeCustomerPortalSessionServiceMock->getWrappedObject(), $this->stripeCustomerPortalConfigurationServiceMock->getWrappedObject()) {
                public function __construct(
                    public CustomerPortalSessionService       $sessions,
                    public CustomerPortalConfigurationService $configurations
                ) {
                }

                public function __get(string $name)
                {
                    return new class($this->sessions, $this->configurations) {
                        public function __construct(
                            public CustomerPortalSessionService       $sessions,
                            public CustomerPortalConfigurationService $configurations
                        ) {
                        }

                        public function __get(string $name)
                        {
                            return match ($name) {
                                'sessions' => $this->sessions,
                                'configurations' => $this->configurations
                            };
                        }
                    };
                }
            });
        return $stripeClientMock;
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(CustomerPortalService::class);
    }

    public function it_should_create_customer_portal_session_with_existing_portal_config_NO_flow_data(): void
    {
        $this->customerPortalConfigurationRepositoryMock->getCustomerPortalConfigurationId()
            ->shouldBeCalledOnce()
            ->willReturn('customerPortalConfigurationId');

        $this->stripeCustomerPortalSessionServiceMock->create([
            'customer' => 'stripeCustomerId',
            'configuration' => 'customerPortalConfigurationId',
            'return_url' => 'https://example.com/redirectUrl',
        ])
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeCustomerPortalSessionMock('https://stripe.com/redirect')
            );

        $this->stripeApiKeyConfigMock->shouldUseTestMode()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->createCustomerPortalSession(
            'stripeCustomerId',
            'https://example.com/redirectUrl',
            null
        )->shouldReturn('https://stripe.com/redirect');
    }

    private function generateStripeCustomerPortalSessionMock(
        string $url
    ): CustomerPortalSession {
        $customerPortalSessionMock = $this->stripeBillingPortalSessionMockFactory->newInstanceWithoutConstructor();
        $this->stripeBillingPortalSessionMockFactory->getProperty('_values')
            ->setValue($customerPortalSessionMock, [
                'url' => $url
            ]);

        return $customerPortalSessionMock;
    }

    public function it_should_create_customer_portal_session_with_existing_portal_config_NO_flow_data_when_test_mode(): void
    {
        $this->customerPortalConfigurationRepositoryMock->getCustomerPortalConfigurationId()
            ->shouldNotBeCalled();

        $this->stripeCustomerPortalSessionServiceMock->create([
            'customer' => 'stripeCustomerId',
            'configuration' => 'test::::customerPortalConfigurationId',
            'return_url' => 'https://example.com/redirectUrl',
        ])
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeCustomerPortalSessionMock('https://stripe.com/redirect')
            );

        $this->stripeApiKeyConfigMock->shouldUseTestMode()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->configMock->get('payments')
            ->shouldBeCalledOnce()
            ->willReturn([
                'stripe' => [
                    'test_customer_portal_id' => 'test::::customerPortalConfigurationId'
                ]
            ]);

        $this->createCustomerPortalSession(
            'stripeCustomerId',
            'https://example.com/redirectUrl',
            null
        )->shouldReturn('https://stripe.com/redirect');
    }

    public function it_should_create_customer_portal_session_with_existing_portal_config_WITH_flow_data(): void
    {
        $this->customerPortalConfigurationRepositoryMock->getCustomerPortalConfigurationId()
            ->shouldBeCalledOnce()
            ->willReturn('customerPortalConfigurationId');

        $flowDataMock = [
            'type' => 'subscription_cancel',
            'after_completion' => [
                'type' => 'redirect',
                'redirect' => [
                    'return_url' => 'https://example.com/api/v3/payments/site-memberships/subscriptions/1/manage/cancel?redirectPath=/memberships',
                ]
            ],
            'subscription_cancel' => [
                'subscription' => 'sub_123'
            ]
        ];

        $this->stripeCustomerPortalSessionServiceMock->create([
            'customer' => 'stripeCustomerId',
            'configuration' => 'customerPortalConfigurationId',
            'return_url' => 'https://example.com/redirectUrl',
            'flow_data' => $flowDataMock
        ])
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeCustomerPortalSessionMock('https://stripe.com/redirect')
            );

        $this->stripeApiKeyConfigMock->shouldUseTestMode()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->createCustomerPortalSession(
            'stripeCustomerId',
            'https://example.com/redirectUrl',
            $flowDataMock
        )->shouldReturn('https://stripe.com/redirect');
    }

    public function it_should_create_customer_portal_session_without_existing_portal_config_NO_flow_data(): void
    {
        $this->customerPortalConfigurationRepositoryMock->getCustomerPortalConfigurationId()
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->configMock->get('site_url')
            ->shouldBeCalledOnce()
            ->willReturn('https://example.com/');

        $this->stripeApiKeyConfigMock->shouldUseTestMode()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->stripeCustomerPortalConfigurationServiceMock->create([
            'business_profile' => [
                'headline' => 'Manage your subscription',
            ],
            'features' => [
                'customer_update' => [
                    'allowed_updates' => [
                        'email',
                    ],
                    'enabled' => true,
                ],
                'invoice_history' => [
                    'enabled' => true,
                ],
                'payment_method_update' => [
                    'enabled' => true,
                ],
                'subscription_cancel' => [
                    'enabled' => true,
                    'mode' => CustomerPortalSubscriptionCancellationModeEnum::AT_PERIOD_END->value,
                ],
                'subscription_pause' => [
                    'enabled' => false,
                ]
            ],
            'default_return_url' => 'https://example.com/'
        ])
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeCustomerPortalConfigurationMock('customerPortalConfigurationId')
            );

        $this->customerPortalConfigurationRepositoryMock->storeCustomerPortalConfiguration(
            'customerPortalConfigurationId'
        )
            ->shouldBeCalledOnce();

        $this->stripeCustomerPortalSessionServiceMock->create([
            'customer' => 'stripeCustomerId',
            'configuration' => 'customerPortalConfigurationId',
            'return_url' => 'https://example.com/redirectUrl',
        ])
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeCustomerPortalSessionMock('https://stripe.com/redirect')
            );

        $this->createCustomerPortalSession(
            'stripeCustomerId',
            'https://example.com/redirectUrl',
            null
        )->shouldReturn('https://stripe.com/redirect');
    }

    private function generateStripeCustomerPortalConfigurationMock(
        string $id
    ): CustomerPortalConfiguration {
        $customerPortalConfigurationMock = $this->stripeBillingPortalConfigurationMockFactory->newInstanceWithoutConstructor();
        $this->stripeBillingPortalConfigurationMockFactory->getProperty('_values')
            ->setValue($customerPortalConfigurationMock, [
                'id' => $id
            ]);

        return $customerPortalConfigurationMock;
    }

    public function it_should_create_customer_portal_session_without_existing_portal_config_WITH_flow_data(): void
    {
        $this->customerPortalConfigurationRepositoryMock->getCustomerPortalConfigurationId()
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->configMock->get('site_url')
            ->shouldBeCalledOnce()
            ->willReturn('https://example.com/');

        $this->stripeApiKeyConfigMock->shouldUseTestMode()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->stripeCustomerPortalConfigurationServiceMock->create([
            'business_profile' => [
                'headline' => 'Manage your subscription',
            ],
            'features' => [
                'customer_update' => [
                    'allowed_updates' => [
                        'email',
                    ],
                    'enabled' => true,
                ],
                'invoice_history' => [
                    'enabled' => true,
                ],
                'payment_method_update' => [
                    'enabled' => true,
                ],
                'subscription_cancel' => [
                    'enabled' => true,
                    'mode' => CustomerPortalSubscriptionCancellationModeEnum::AT_PERIOD_END->value,
                ],
                'subscription_pause' => [
                    'enabled' => false,
                ]
            ],
            'default_return_url' => 'https://example.com/'
        ])
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeCustomerPortalConfigurationMock('customerPortalConfigurationId')
            );

        $this->customerPortalConfigurationRepositoryMock->storeCustomerPortalConfiguration(
            'customerPortalConfigurationId'
        )
            ->shouldBeCalledOnce();

        $flowDataMock = [
            'type' => 'subscription_cancel',
            'after_completion' => [
                'type' => 'redirect',
                'redirect' => [
                    'return_url' => 'https://example.com/api/v3/payments/site-memberships/subscriptions/1/manage/cancel?redirectPath=/memberships',
                ]
            ],
            'subscription_cancel' => [
                'subscription' => 'sub_123'
            ]
        ];

        $this->stripeCustomerPortalSessionServiceMock->create([
            'customer' => 'stripeCustomerId',
            'configuration' => 'customerPortalConfigurationId',
            'return_url' => 'https://example.com/redirectUrl',
            'flow_data' => $flowDataMock
        ])
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeCustomerPortalSessionMock('https://stripe.com/redirect')
            );

        $this->createCustomerPortalSession(
            'stripeCustomerId',
            'https://example.com/redirectUrl',
            $flowDataMock
        )->shouldReturn('https://stripe.com/redirect');
    }
}
