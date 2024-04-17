<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Webhooks\Services;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Core\Payments\Stripe\Webhooks\Enums\WebhookEventTypeEnum;
use Minds\Core\Payments\Stripe\Webhooks\Model\SubscriptionsWebhookDetails;
use Minds\Core\Payments\Stripe\Webhooks\Repositories\WebhooksConfigurationRepository;
use Minds\Exceptions\ServerErrorException;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Stripe\WebhookEndpoint;
use UnexpectedValueException;

class SubscriptionsWebhookService
{
    private ?StripeClient $stripeClient = null;
    public function __construct(
        private readonly Config $config,
        private readonly WebhooksConfigurationRepository $webhooksConfigurationRepository,
    )
    {
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
                'url' => $this->config->get('site_url') . 'api/v3/stripe/webhooks/site-memberships/process-renewal',
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
            webhookSecret: $response->secret,
            webhookDomainUrl: $this->config->get('site_url')
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
            (
                $existingWebhookDetails->stripeWebhookDomainUrl === $this->config->get('site_url') &&
                ($webhookEndpointCheckDetails = $this->checkSubscriptionsWebhook($existingWebhookDetails->stripeWebhookId)) &&
                $webhookEndpointCheckDetails->url === $this->config->get('site_url') . 'api/v3/stripe/webhooks/site-memberships/process-renewal'
            )
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
    ): Event
    {
        try {
            return Webhook::constructEvent(
                payload: $payload,
                sigHeader: $signature,
                secret: $secret
            );
        } catch (UnexpectedValueException $e) {
            throw new ServerErrorException('Failed to construct event', previous: $e);
        } catch (SignatureVerificationException $e) {
            throw new ServerErrorException('Failed to verify signature', previous: $e);
        }
    }

    public function initStripeClient(): void
    {
        $this->stripeClient ??= Di::_()->get(StripeClient::class);
    }
}
