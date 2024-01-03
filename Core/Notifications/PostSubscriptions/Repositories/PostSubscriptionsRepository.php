<?php
namespace Minds\Core\Notifications\PostSubscriptions\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class PostSubscriptionsRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_post_notification_subscriptions';

    /**
     * Returns a single PostSubscription, if exists
     */
    public function get(int $userGuid, int $entityGuid): ?PostSubscription
    {
        $rows = iterator_to_array($this->getList($userGuid, $entityGuid));

        if ($rows) {
            return $rows[0];
        } else {
            return null;
        }
    }

    /**
     * Returns multiple PostSubscription entities based on inputs
     * @return iterable<PostSubscription>
     */
    public function getList(
        int $userGuid = null,
        int $entityGuid = null,
        PostSubscriptionFrequencyEnum $frequency = null,
    ): iterable {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(static::TABLE_NAME)
            ->columns([
                'user_guid',
                'entity_guid',
                'frequency',
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        $values = [
            'tenant_id' => $this->getTenantId(),
        ];

        if ($userGuid) {
            $query->where('user_guid', Operator::EQ, new RawExp(':user_guid'));
            $values['user_guid'] = $userGuid;
        }

        if ($entityGuid) {
            $query->where('entity_guid', Operator::EQ, new RawExp(':entity_guid'));
            $values['entity_guid'] = $entityGuid;
        }

        if ($frequency) {
            $query->where('frequency', Operator::EQ, new RawExp(':frequency'));
            $values['frequency'] = $frequency->name;
        }

        $stmt = $query->prepare();

        $stmt->execute($values);

        if (!$stmt->rowCount()) {
            return null;
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            yield new PostSubscription(
                userGuid: $row['user_guid'],
                entityGuid: $row['entity_guid'],
                frequency: constant(PostSubscriptionFrequencyEnum::class . '::' . $row['frequency']),
            );
        }
    }

    /**
     * Create or update the subscription
     */
    public function upsert(PostSubscription $postSubscription): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(static::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'user_guid' => new RawExp(':user_guid'),
                'entity_guid' => new RawExp(':entity_guid'),
                'frequency' => new RawExp(':frequency'),
            ])
            ->onDuplicateKeyUpdate([
                'frequency' => new RawExp(':frequency'),
            ]);

        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'user_guid' => $postSubscription->userGuid,
            'entity_guid' => $postSubscription->entityGuid,
            'frequency' => $postSubscription->frequency->name,
        ]);
    }

    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
