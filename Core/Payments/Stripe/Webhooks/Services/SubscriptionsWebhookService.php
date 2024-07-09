<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Webhooks\Services;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Core\Payments\Stripe\Webhooks\Enums\WebhookEventTypeEnum;
use Minds\Core\Payments\Stripe\Webhooks\Model\SubscriptionsWebhookDetails;
use Minds\Core\Payments\Stripe\Webhooks\Repositories\WebhooksConfigurationRepository;
use Minds\Exceptions\ServerErrorException;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\WebhookEndpoint;

class SubscriptionsWebhookService
{
    protected ?StripeClient $stripeClient = null;
    
    const PROCESS_RENEWAL_ENDPOINT = '/api/v3/stripe/webhooks/site-memberships/process-renewal';

    public function __construct(
        private readonly Config $config,
        private readonly WebhooksConfigurationRepository $webhooksConfigurationRepository,
        private readonly WebhookEventBuilderService $webhookEventBuilderService,
        private readonly DomainService $domainService,
    ) {
    }

    /**
     * @return bool
     * @throws ServerErrorException
     */
    public function createSubscriptionsWebhook(): bool
    {
        $this->initStripeClient();

        if (!$this->shouldCreateSubscriptionsWebhook()) {
            return true;
        }

        /**
         * @var WebhookEndpoint $response
         */
        $response = $this->stripeClient
            ->webhookEndpoints
            ->create([
                'url' => $this->buildWebhookEndpoint(),
                'enabled_events' => [
                    WebhookEventTypeEnum::INVOICE_PAYMENT_SUCCEEDED->value,
                    WebhookEventTypeEnum::INVOICE_PAID->value,
                    WebhookEventTypeEnum::INVOICE_PAYMENT_FAILED->value,
                ],
                'metadata' => [
                    'description' => 'Site memberships webhook endpoint',
                    'tenant_id' => $this->config->get('tenant_id') ?? -1,
                ]
            ]);

        return $this->webhooksConfigurationRepository->storeWebhookConfiguration(
            webhookId: $response->id,
            webhookSecret: $response->secret
        );
    }

    /**
     * @return SubscriptionsWebhookDetails
     * @throws ServerErrorException
     */
    public function getSubscriptionsWebhookDetails(): SubscriptionsWebhookDetails
    {
        return $this->webhooksConfigurationRepository->getWebhookConfiguration();
    }

    /**
     * @param string $subscriptionId
     * @return WebhookEndpoint|null
     */
    private function checkSubscriptionsWebhook(string $subscriptionId): ?WebhookEndpoint
    {
        try {
            return $this->stripeClient
                ->webhookEndpoints
                ->retrieve($subscriptionId);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    /**
     * @return bool
     * @throws ServerErrorException
     */
    private function shouldCreateSubscriptionsWebhook(): bool
    {
        $existingWebhookDetails = $this->getSubscriptionsWebhookDetails();
        return !(
            $existingWebhookDetails->stripeWebhookId &&
            ($webhookEndpointCheckDetails = $this->checkSubscriptionsWebhook($existingWebhookDetails->stripeWebhookId)) &&
            $webhookEndpointCheckDetails->url === $this->buildWebhookEndpoint()
        );
    }

    /**
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return Event
     * @throws ServerErrorException
     */
    public function buildWebhookEvent(
        string $payload,
        string $signature,
        string $secret
    ): Event {
        return $this->webhookEventBuilderService->buildWebhookEvent(
            payload: $payload,
            signature: $signature,
            secret: $secret
        );
    }

    /**
     * @param StripeClient $stripeClient
     * @param Config $config
     * @param WebhooksConfigurationRepository $webhooksConfigurationRepository
     * @param WebhookEventBuilderService $webhookEventBuilderService
     * @return self
     */
    public static function createForUnitTests(
        StripeClient $stripeClient,
        Config $config,
        WebhooksConfigurationRepository $webhooksConfigurationRepository,
        WebhookEventBuilderService $webhookEventBuilderService,
        DomainService $domainService
    ): self {
        $instance = new self(
            config: $config,
            webhooksConfigurationRepository: $webhooksConfigurationRepository,
            webhookEventBuilderService: $webhookEventBuilderService,
            domainService: $domainService
        );
        $instance->stripeClient = $stripeClient;

        return $instance;
    }

    public function initStripeClient(): void
    {
        $this->stripeClient ??= Di::_()->get(StripeClient::class);
    }

    /**
     * Builds callback URL for webhook.
     * @return string Callback URL.
     */
    private function buildWebhookEndpoint(): string
    {
        return 'https://' . $this->domainService->buildTmpSubdomain($this->config->get('tenant')) . self::PROCESS_RENEWAL_ENDPOINT;
    }
}
