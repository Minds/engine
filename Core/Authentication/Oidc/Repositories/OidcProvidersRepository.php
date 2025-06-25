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
     * @return OidcProvider[]
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
                'configs',
            ])
            ->from(self::TABLE_NAME);

        $tenantId = $this->config->get('tenant_id') ?: -1;

        $query->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));
        $values['tenant_id'] = $tenantId;

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
     * Adds a provider to the datastore
     */
    public function addProvider(OidcProvider $provider): OidcProvider
    {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?: -1,
                'name' => new RawExp(':name'),
                'issuer' => new RawExp(':issuer'),
                'client_id' => new RawExp(':client_id'),
                'client_secret' => new RawExp(':client_secret'),
                'configs' => new RawExp(':configs'),
            ])
            ->prepare();

        $stmt->execute([
            'name' => $provider->name,
            'issuer' => $provider->issuer,
            'client_id'  => $provider->clientId,
            'client_secret' => $provider->clientSecretCipherText,
            'configs' => json_encode($provider->configs),
        ]);

        $providerId = $this->mysqlClientWriter->lastInsertId();
        
        return new OidcProvider(
            id: $providerId,
            name: $provider->name,
            issuer: $provider->issuer,
            clientId: $provider->clientId,
            clientSecretCipherText: $provider->clientSecretCipherText,
            configs: $provider->configs,
        );
    }

    /**
     * Updates a provider with changed fields
     */
    public function updateProvider(
        int $providerId,
        ?string $name = null,
        ?string $issuer = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?array $configs = null,
    ): OidcProvider {
        $this->beginTransaction();

        $updatedFields = [];

        if ($name) {
            $updatedFields['name'] = $name;
        }
        
        if ($issuer) {
            $updatedFields['issuer'] = $issuer;
        }

        if ($clientId) {
            $updatedFields['client_id'] = $clientId;
        }

        if ($clientSecret) {
            $updatedFields['client_secret'] = $clientSecret;
        }

        if ($configs) {
            $updatedFields['configs'] = json_encode($configs);
        }

        $set = [];
        foreach ($updatedFields as $k => $v) {
            $set[$k] = new RawExp(":$k");
        }

        $stmt = $this->mysqlClientWriterHandler->update()
            ->table(self::TABLE_NAME)
            ->set($set)
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?: -1)
            ->where('provider_id', Operator::EQ, $providerId)
            ->prepare();

        $stmt->execute($updatedFields);

        $this->commitTransaction();

        return $this->getProviders($providerId)[0];
    }

    /**
     * Removes a provider from the datastore
     */
    public function deleteProvider(int $providerId): bool
    {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?: -1)
            ->where('provider_id', Operator::EQ, $providerId)
            ->prepare();

        return $stmt->execute();
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
            clientSecretCipherText: $row['client_secret'],
            configs: json_decode($row['configs'], true) ?: [],
        );
    }
}
