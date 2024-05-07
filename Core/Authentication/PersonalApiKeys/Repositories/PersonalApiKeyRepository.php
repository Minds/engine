<?php
namespace Minds\Core\Authentication\PersonalApiKeys\Repositories;

use DateTimeImmutable;
use Minds\Core\Authentication\PersonalApiKeys\PersonalApiKey;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Router\Enums\ApiScopeEnum;
use Minds\Exceptions\NotFoundException;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class PersonalApiKeyRepository extends AbstractRepository
{
    public const KEYS_TABLE = 'minds_personal_api_keys';
    public const SCOPES_TABLE = 'minds_personal_api_key_scopes';

    /**
     * Saves the personal api key to the database
     */
    public function add(PersonalApiKey $personalApiKey): bool
    {
        $this->beginTransaction();

        try {
            $keysQuery = $this->mysqlClientWriterHandler->insert()
                ->into(self::KEYS_TABLE)
                ->set([
                    'tenant_id' => $this->getTenantId(),
                    'id' => new RawExp(':id'),
                    'owner_guid' => new RawExp(':owner_guid'),
                    'secret_hash' => new RawExp(':secret_hash'),
                    'name' => new RawExp(':name'),
                    'created_timestamp' => new RawExp(':created_timestamp'),
                    'expires_timestamp' => new RawExp(':expires_timestamp'),
                ]);

            $keysStmt = $keysQuery->prepare();
            $keysStmt->execute([
                'id' => $personalApiKey->id,
                'owner_guid' => $personalApiKey->ownerGuid,
                'secret_hash' => $personalApiKey->secretHash,
                'name' => $personalApiKey->name,
                'created_timestamp' => $personalApiKey->timeCreated->format('c'),
                'expires_timestamp' => $personalApiKey->timeExpires?->format('c'),
            ]);

            foreach ($personalApiKey->scopes as $scope) {
                $scopesQuery = $this->mysqlClientWriterHandler->insert()
                    ->into(self::SCOPES_TABLE)
                    ->set([
                        'tenant_id' => $this->getTenantId(),
                        'id' => new RawExp(':id'),
                        'scope' => new RawExp(':scope'),
                    ]);

                $scopesStmt = $scopesQuery->prepare();

                $scopesStmt->execute([
                    'id' => $personalApiKey->id,
                    'scope' => $scope->name,
                ]);
            }
            $this->commitTransaction();
        } catch(\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
        
        return true;
    }

    /**
     * Returns a list of the personal api keys
     * @return PersonalApiKey[]
     */
    public function getList(
        int $ownerGuid,
    ): array {
        $query = $this->buildReadBaseQuery()
            ->where('owner_guid', Operator::EQ, new RawExp(':owner_guid'));

        $stmt = $query->prepare();

        $stmt->execute([
            'owner_guid' => $ownerGuid,
        ]);

        if (!$stmt->rowCount()) {
            return [];
        }

        return array_map(fn ($row) => $this->buildModelFromRow($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Returns a single api key, if it exists
     */
    public function getById(string $id, int $ownerGuid): ?PersonalApiKey
    {
        $query = $this->buildReadBaseQuery()
            ->where('owner_guid', Operator::EQ, new RawExp(':owner_guid'))
            ->where('k.id', Operator::EQ, new RawExp(':id'));

        $stmt = $query->prepare();

        $stmt->execute([
            'id' => $id,
            'owner_guid' => $ownerGuid,
        ]);

        if (!$stmt->rowCount()) {
            return null;
        }

        return $this->buildModelFromRow($stmt->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * Returns a personal api key from its secret hash
     */
    public function getBySecretHash(string $secretHash): ?PersonalApiKey
    {
        $query = $this->buildReadBaseQuery()
            ->where('k.secret_hash', Operator::EQ, new RawExp(':secret_hash'));

        $stmt = $query->prepare();

        $stmt->execute([
            'secret_hash' => $secretHash,
        ]);

        if (!$stmt->rowCount()) {
            return null;
        }

        return $this->buildModelFromRow($stmt->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * Deletes a key and its scopes
     */
    public function delete(string $id, int $ownerGuid): bool
    {
        $this->beginTransaction();

        try {
            $scopeQuery = $this->mysqlClientWriterHandler->delete()
                ->from(self::SCOPES_TABLE)
                ->where('tenant_id', Operator::EQ, $this->getTenantId())
                ->where('id', Operator::EQ, new RawExp(':id'));

            $scopeStmt = $scopeQuery->prepare();
            $scopeStmt->execute([
                'id' => $id,
            ]);

            $query = $this->mysqlClientWriterHandler->delete()
                ->from(self::KEYS_TABLE)
                ->where('tenant_id', Operator::EQ, $this->getTenantId())
                ->where('owner_guid', Operator::EQ, new RawExp(':owner_guid'))
                ->where('id', Operator::EQ, new RawExp(':id'));

            $stmt = $query->prepare();
            $stmt->execute([
                'owner_guid' => $ownerGuid,
                'id' => $id,
            ]);
            $success = $stmt->rowCount() > 0;

            if ($success) {
                $this->commitTransaction();
            } else {
                throw new NotFoundException("Unable to confirm delete. The key may no longer exist.");
            }
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
        
        return $success;
    }

    /**
     * Builds the PersonalApiKey from a row
     */
    private function buildModelFromRow(array $row): PersonalApiKey
    {
        return new PersonalApiKey(
            id: $row['id'],
            ownerGuid: (int) $row['owner_guid'],
            secretHash: $row['secret_hash'],
            name: $row['name'],
            scopes: $row['scopes'] ? array_map(fn ($scope) => constant(ApiScopeEnum::class . '::' . $scope), explode(',', $row['scopes'])) : [],
            timeCreated: new DateTimeImmutable($row['created_timestamp']),
            timeExpires: isset($row['expires_timestamp']) ? new DateTimeImmutable($row['expires_timestamp']) : null,
        );
        ;
    }

    /**
     * Builds out the base query for reading keys
     */
    private function buildReadBaseQuery(): SelectQuery
    {
        return $this->mysqlClientReaderHandler->select()
            ->columns([
                'k.*',
                'scopes' => new RawExp('GROUP_CONCAT(scope)'),
            ])
            ->from(new RawExp(self::KEYS_TABLE . ' as k'))
            ->leftJoinRaw(['s' => self::SCOPES_TABLE], "k.id = s.id AND k.tenant_id = s.tenant_id")
            ->where('k.tenant_id', Operator::EQ, $this->getTenantId())
            ->groupBy('k.tenant_id', 'k.id');
    }

    /**
     * -1 if not tenant
     */
    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
