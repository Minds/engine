<?php
namespace Minds\Core\Authentication\Oidc\Repositories;

use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class OidcProvidersRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_oidc_providers';

    /**
     * Return a list of providers setup for a site
     * @return OidcProviders[]
     */
    public function getProviders(?int $providerId = null): array
    {
        $values = [];

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'provider_id',
                'name',
                'issuer',
                'client_id',
                'client_secret',
            ])
            ->from(self::TABLE_NAME);

        if ($tenantId = $this->config->get('tenant_id')) {
            $query->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));
            $values['tenant_id'] = $tenantId;
        } else {
            $query->where('tenant_id', Operator::IS, null);
        }

        if ($providerId) {
            $query->where('provider_id', Operator::EQ, new RawExp(':provider_id'));
            $values['provider_id'] = $providerId;
        }

        $stmt = $query->prepare();

        $stmt->execute($values);

        // If no records, there is no match
        if ($stmt->rowCount() === 0) {
            return [];
        }

        $providers = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $providers[] = $this->buildProviderFromRow($row);
        }

        return $providers;
    }

    /**
     * Builds a OidcProvider model from an array
     */
    private function buildProviderFromRow(array $row): OidcProvider
    {
        return new OidcProvider(
            id: (int) $row['provider_id'],
            name: $row['name'] ?: "Oidc",
            issuer: $row['issuer'],
            clientId: $row['client_id'],
            clientSecret: $row['client_secret'],
        );
    }
}
