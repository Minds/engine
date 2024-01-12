<?php
namespace Minds\Core\Notifications\Push\Config;

use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class PushNotificationsConfigRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_push_notification_config';

    /**
     * Returns the configs for push notifications (if found in the database)
     */
    public function get(int $tenantId): ?PushNotificationConfig
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(static::TABLE_NAME)
            ->columns([
                'apns_team_id',
                'apns_key',
                'apns_key_id',
                'apns_topic',
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        $stmt = $query->prepare();
        $stmt->execute([
            'tenant_id' => $tenantId,
        ]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $row = $rows[0];

        return new PushNotificationConfig(
            apnsTeamId: $row['apns_team_id'],
            apnsKey: $row['apns_key'],
            apnsKeyId: $row['apns_key_id'],
            apnsTopic: $row['apns_topic']
        );
    }
}
