<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Repositories;

use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Enums\MultiTenantCustomHostnameStatusEnum;
use Minds\Core\MultiTenant\Types\MultiTenantDomain;
use PDO;
use Selective\Database\Operator;

class DomainsRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_tenants_domain_details';

    /**
     * @param int $tenantId
     * @param string $cloudflareId
     * @param string $domain
     * @param MultiTenantCustomHostnameStatusEnum $status
     * @return void
     * @throws Exception
     */
    public function storeDomainDetails(
        int $tenantId,
        string $cloudflareId,
        string $domain,
        MultiTenantCustomHostnameStatusEnum $status,
    ): void {
        $statement = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $tenantId,
                'cloudflare_id' => $cloudflareId,
                'domain' => $domain,
                'status' => $status->value,
            ])
            ->onDuplicateKeyUpdate([
                'domain' => $domain,
                'cloudflare_id' => $cloudflareId,
                'status' => $status->value,
            ])
            ->execute();

        if (!$statement) {
            throw new Exception("Could not store domain details for tenant: " . $tenantId);
        }
    }

    /**
     * @param int $tenantId
     * @return MultiTenantDomain
     * @throws Exception
     */
    public function getDomainDetails(
        int $tenantId
    ): MultiTenantDomain {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'tenant_id',
                'cloudflare_id',
                'domain',
                'status',
                'created_at',
            ])
            ->where('tenant_id', Operator::EQ, $tenantId)
            ->execute();

        if ($statement->rowCount() === 0) {
            throw new Exception("Could not find domain details for tenant: " . $tenantId);
        }

        $item = $statement->fetch(PDO::FETCH_ASSOC);

        return new MultiTenantDomain(
            tenantId: $item['tenant_id'],
            domain: $item['domain'],
            status: MultiTenantCustomHostnameStatusEnum::from($item['status']),
            createdAt: strtotime($item['created_at']),
            cloudflareId: $item['cloudflare_id']
        );
    }
}
