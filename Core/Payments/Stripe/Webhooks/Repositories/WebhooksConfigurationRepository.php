<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Webhooks\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\Stripe\Webhooks\Model\SubscriptionsWebhookDetails;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class WebhooksConfigurationRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_payments_config';

    /**
     * @param string $webhookId
     * @param string $webhookSecret
     * @param string $webhookDomainUrl
     * @return bool
     * @throws ServerErrorException
     */
    public function storeWebhookConfiguration(
        string $webhookId,
        string $webhookSecret,
        string $webhookDomainUrl
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'stripe_webhook_id' => new RawExp(':stripe_webhook_id'),
                'stripe_webhook_secret' => new RawExp(':stripe_webhook_secret'),
                'stripe_webhook_domain_url' => new RawExp(':stripe_webhook_domain_url'),
            ])
            ->onDuplicateKeyUpdate([
                'stripe_webhook_id' => new RawExp(':stripe_webhook_id'),
                'stripe_webhook_secret' => new RawExp(':stripe_webhook_secret'),
                'stripe_webhook_domain_url' => new RawExp(':stripe_webhook_domain_url'),
            ])
            ->prepare();

        try {
            return $stmt->execute([
                'stripe_webhook_id' => $webhookId,
                'stripe_webhook_secret' => $webhookSecret,
                'stripe_webhook_domain_url' => $webhookDomainUrl,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to store customer portal configuration', previous: $e);
        }
    }

    /**
     * @return SubscriptionsWebhookDetails|null
     * @throws ServerErrorException
     */
    public function getWebhookConfiguration(): ?SubscriptionsWebhookDetails
    {
        $stmt = $this->mysqlClientWriterHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'stripe_webhook_id',
                'stripe_webhook_secret',
                'stripe_webhook_domain_url'
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->prepare();

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                return new SubscriptionsWebhookDetails();
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return new SubscriptionsWebhookDetails(
                stripeWebhookId: $result['stripe_webhook_id'],
                stripeWebhookSecret: $result['stripe_webhook_secret'],
                stripeWebhookDomainUrl: $result['stripe_webhook_domain_url']
            );
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to get customer portal configuration', previous: $e);
        }
    }
}
