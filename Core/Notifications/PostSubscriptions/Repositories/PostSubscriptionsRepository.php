<?php
namespace Minds\Core\Notifications\PostSubscriptions\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Models\Tenant;
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
        ?int $userGuid = null,
        ?int $entityGuid = null,
        ?PostSubscriptionFrequencyEnum $frequency = null,
    ): iterable {
        /** @var Tenant|null */
        $tenant = $this->config->get('tenant');
        $globalMode = $tenant?->config->globalMode ?: false;

        $query = $this->mysqlClientReaderHandler->select()
            ->from(new RawExp(self::TABLE_NAME . ' as ps'))
            ->columns([
                'user_guid',
                'entity_guid',
                'frequency',
            ]);

        $values = [
            'tenant_id' => $this->getTenantId(),
        ];

        /**
         * If global mode is on, we should do a join between
         */
        if ($globalMode) {
            $query = $this->mysqlClientReaderHandler->select()
                ->from(new RawExp('minds_entities_user as u'))
                ->leftJoinRaw(['ps' => self::TABLE_NAME], 'u.tenant_id = ps.tenant_id AND u.guid = ps.user_guid AND ps.entity_guid = :entity_guid')
                ->columns([
                    'user_guid' => new RawExp('u.guid'),
                    'entity_guid' => new RawExp("'" . (int) $entityGuid . "'"),
                    'frequency' => new RawExp("COALESCE(ps.frequency, 'ALWAYS')"),
                ])
                ->where('u.tenant_id', Operator::EQ, new RawExp(':tenant_id'));
        } else {
            $query->where('ps.tenant_id', Operator::EQ, new RawExp(':tenant_id'));
        }

        if ($userGuid) {
            if ($globalMode) {
                $query->where('u.guid', Operator::EQ, new RawExp(':user_guid'));
            } else {
                $query->where('user_guid', Operator::EQ, new RawExp(':user_guid'));
            }
            $values['user_guid'] = $userGuid;
        }

        if ($entityGuid) {
            if (!$globalMode) {
                $query->where('entity_guid', Operator::EQ, new RawExp(':entity_guid'));
            }
            $values['entity_guid'] = $entityGuid;
        }

        // Global mode will post filter
        if ($frequency) {
            if ($globalMode) {
                $query->having('frequency', Operator::EQ, new RawExp(':frequency'));
            } else {
                $query->where('frequency', Operator::EQ, new RawExp(':frequency'));
            }
            $values['frequency'] = $frequency->name;
        }

        $stmt = $query->prepare();

        $stmt->execute($values);

        if (!$stmt->rowCount()) {
            return null;
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            if ($row['user_guid'] === $row['entity_guid']) {
                continue; // Do not allow post notifications to self
            }
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
