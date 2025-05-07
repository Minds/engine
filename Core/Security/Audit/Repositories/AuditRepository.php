<?php
declare(strict_types=1);

namespace Minds\Core\Security\Audit\Repositories;

use DateTimeImmutable;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Helpers\PermissionIntentHelpers;
use Minds\Core\Security\Rbac\Models\PermissionIntent;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Repository for permission intents.
 */
class AuditRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_audit_log';

    public function log(
        string $event,
        int $userGuid,
        array $properties,
        string $ipAddress,
        string $userAgent,
        string $referrer,
    ): bool {
        $tenantId = $this->config->get('tenant_id') ?: -1;

        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $tenantId,
                'event' => new RawExp(':event'),
                'user_guid' => new RawExp(':userGuid'),
                'properties' => new RawExp(':properties'),
                'ip_address' => new RawExp(':ipAddress'),
                'user_agent' => new RawExp(':userAgent'),
                'referrer' => new RawExp(':referrer'),
            ]);

        $stmt = $query->prepare();
        return $stmt->execute([
            'event' => $event,
            'userGuid' => $userGuid,
            'properties' => json_encode($properties),
            'ipAddress' => $ipAddress,
            'userAgent' => $userAgent,
            'referrer' => $referrer,
        ]);
    }

    public function list(
        int $limit,
        ?DateTimeImmutable $timestampGt = null,
        ?DateTimeImmutable $timestampLt = null,
        ?int $afterId = null,
        ?int $beforeId = null,
    ): array {
        $tenantId = $this->config->get('tenant_id') ?: -1;

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'event',
                'event_id',
                'user_guid',
                'properties',
                'ip_address',
                'user_agent',
                'referrer',
                'created_at',
            ])
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, $tenantId)
            ->orderBy('created_at desc')
            ->limit($limit);

        if ($timestampGt) {
            $query->where('created_at', Operator::GT, $timestampGt->format('c'));
        }

        if ($timestampLt) {
            $query->where('created_at', Operator::LT, $timestampLt->format('c'));
        }

        if ($afterId) {
            $query->where('event_id', Operator::GT, $afterId);
        }

        if ($beforeId) {
            $query->where('event_id', Operator::LT, $beforeId);
        }
        
        $stmt = $query->prepare();

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        return array_map(function ($row) {
            $row['user_guid'] = (string) $row['user_guid'];
            $row['properties'] = json_decode($row['properties'], true);
            return $row;
        }, $rows);
    }
}
