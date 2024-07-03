<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\Stripe\Webhooks\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Core\Payments\Stripe\Webhooks\Enums\WebhookEventTypeEnum;
use Minds\Core\Payments\Stripe\Webhooks\Model\SubscriptionsWebhookDetails;
use Minds\Core\Payments\Stripe\Webhooks\Repositories\WebhooksConfigurationRepository;
use Minds\Core\Payments\Stripe\Webhooks\Services\SubscriptionsWebhookService;
use Minds\Core\Payments\Stripe\Webhooks\Services\WebhookEventBuilderService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;
use ReflectionException;
use Stripe\Service\WebhookEndpointService;
use Stripe\WebhookEndpoint;

class SubscriptionsWebhookServiceSpec extends ObjectBehavior
{
    private Collaborator $stripeWebhookEndpointServiceMock;
    private Collaborator $configMock;
    private Collaborator $webhooksConfigurationRepositoryMock;
    private Collaborator $webhookEventBuilderServiceMock;
    private Collaborator $domainServiceMock;

    private ReflectionClass $stripeClientMockFactory;
    private ReflectionClass $stripeWebhookEndpointMockFactory;

    /**
     * @param WebhookEndpointService $stripeWebhookEndpointService
     * @param Config $config
     * @param WebhooksConfigurationRepository $webhooksConfigurationRepository
     * @param WebhookEventBuilderService $webhookEventBuilderService
     * @return void
     * @throws ReflectionException
     */
    public function let(
        WebhookEndpointService $stripeWebhookEndpointService,
        Config $config,
        WebhooksConfigurationRepository $webhooksConfigurationRepository,
        WebhookEventBuilderService $webhookEventBuilderService,
        DomainService $domainServiceMock
    ): void {
        $this->stripeWebhookEndpointServiceMock = $stripeWebhookEndpointService;
        $this->configMock = $config;
        $this->webhooksConfigurationRepositoryMock = $webhooksConfigurationRepository;
        $this->webhookEventBuilderServiceMock = $webhookEventBuilderService;
        $this->domainServiceMock = $domainServiceMock;

        $this->stripeClientMockFactory = new ReflectionClass(StripeClient::class);
        $this->stripeWebhookEndpointMockFactory = new ReflectionClass(WebhookEndpoint::class);
        
        $this->beConstructedThrough(
            'createForUnitTests',
            [
                $this->prepareStripeClientMock(),
                $this->configMock,
                $this->webhooksConfigurationRepositoryMock,
                $this->webhookEventBuilderServiceMock,
                $this->domainServiceMock
            ]
        );
    }

    /**
     * @return StripeClient
     * @throws ReflectionException
     */
    private function prepareStripeClientMock(): StripeClient
    {
        $stripeClientMock = $this->stripeClientMockFactory->newInstanceWithoutConstructor();
        $this->stripeClientMockFactory->getProperty('coreServiceFactory')
            ->setValue($stripeClientMock, new class($this->stripeWebhookEndpointServiceMock->getWrappedObject()) {
                public function __construct(
                    public WebhookEndpointService $webhookEndpoints
                ) {
                }

                public function __get(string $name)
                {
                    return $this->webhookEndpoints;
                }
            });
        return $stripeClientMock;
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(SubscriptionsWebhookService::class);
    }

    public function it_should_create_subscriptions_webhook(
        Tenant $tenantMock
    ): void {
        $this->configMock->get('tenant_id')
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->configMock->get('tenant')
            ->shouldBeCalledOnce()
            ->willReturn($tenantMock);

        $subscriptionsWebhookDetailsMock = new SubscriptionsWebhookDetails();

        $this->webhooksConfigurationRepositoryMock->getWebhookConfiguration()
            ->shouldBeCalledOnce()
            ->willReturn($subscriptionsWebhookDetailsMock);

        $this->stripeWebhookEndpointServiceMock->retrieve('webhookId')
            ->shouldNotBeCalled();

        $this->domainServiceMock->buildTmpSubdomain($tenantMock)
            ->shouldBeCalledOnce()
            ->willReturn('example.com');

        $this->stripeWebhookEndpointServiceMock->create([
            'url' => 'https://example.com/api/v3/stripe/webhooks/site-memberships/process-renewal',
            'enabled_events' => [
                WebhookEventTypeEnum::INVOICE_PAYMENT_SUCCEEDED->value,
                WebhookEventTypeEnum::INVOICE_PAID->value,
                WebhookEventTypeEnum::INVOICE_PAYMENT_FAILED->value,
            ],
            'metadata' => [
                'description' => 'Site memberships webhook endpoint',
                'tenant_id' => 1,
            ]
        ])
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateWebhookEndpointMock(
                    'webhookId',
                    'webhookSecret'
                )
            );

        $this->webhooksConfigurationRepositoryMock->storeWebhookConfiguration(
            'webhookId',
            'webhookSecret'
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->createSubscriptionsWebhook()
            ->shouldEqual(true);
    }

    public function it_should_get_subscriptions_webhook_details(
        SubscriptionsWebhookDetails $subscriptionsWebhookDetailsMock
    ): void {
        $this->webhooksConfigurationRepositoryMock->getWebhookConfiguration()
            ->shouldBeCalledOnce()
            ->willReturn($subscriptionsWebhookDetailsMock);

        $this->getSubscriptionsWebhookDetails()
            ->shouldBeAnInstanceOf(SubscriptionsWebhookDetails::class);
    }

    private function generateWebhookEndpointMock(
        string $id,
        string $secret
    ): WebhookEndpoint {
        $mock = $this->stripeWebhookEndpointMockFactory->newInstanceWithoutConstructor();
        $this->stripeWebhookEndpointMockFactory->getProperty('_values')
            ->setValue($mock, [
                'id' => $id,
                'secret' => $secret
            ]);

        return $mock;
    }
}
