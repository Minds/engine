<?php
namespace Minds\Core\Media\Audio;

use DateTimeImmutable;
use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class AudioRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_entities_audio';
    const ENTITIES_TABLE_NAME = 'minds_entities';

    /**
     * Saves an audio entity to the database
     */
    public function add(AudioEntity $audio): bool
    {
        $this->beginTransaction();

        $stmt = $this->mysqlClientWriterHandler
            ->insert()
            ->into(self::ENTITIES_TABLE_NAME)
            ->set([
                'tenant_id' => $this->getTenantId(),
                'guid' => new RawExp(':guid'),
                'owner_guid' => new RawExp(':owner_guid'),
                'type' => $audio->getType(),
                'subtype' => $audio->getSubtype(),
                'access_id' => $audio->getAccessId(),
            ])
            ->prepare();

        $success = $stmt->execute([
            'guid' => $audio->guid,
            'owner_guid' => $audio->ownerGuid,
        ]);

        if (!$success) {
            $this->rollbackTransaction();
            return false;
        }

        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->getTenantId(),
                'guid' => new RawExp(':guid'),
                'remote_file_url' => new RawExp(':remote_file_url'),
                'created_at' => new RawExp(':created_at'),
            ])
            ->prepare();
        
        $success = $stmt->execute([
            'guid' => $audio->guid,
            'remote_file_url' => $audio->remoteFileUrl,
            'created_at' => date('c'),
        ]);
        
        if ($success) {
            $this->commitTransaction();
        } else {
            $this->rollbackTransaction();
            return false;
        }

        return true;
    }

    /**
     * Updates fields of the entity
     */
    public function update(AudioEntity $audioEntity, array $fields): bool
    {
        $query = $this->mysqlClientWriterHandler->update()
            ->table(self::TABLE_NAME)
            ->where('guid', Operator::EQ, new RawExp(':guid'))
            ->where('tenant_id', Operator::EQ, $this->getTenantId());

        $set = [];

        $values = [
            'guid' => $audioEntity->guid,
        ];

        foreach ($fields as $field) {
            switch ($field) {
                case 'uploadedAt':
                    $set['uploaded_at'] = new RawExp(':uploadedAt');
                    $values[$field] = $audioEntity->uploadedAt->format('c');
                    break;
                case 'processedAt':
                    $set['processed_at'] = new RawExp(':processedAt');
                    $values[$field] = $audioEntity->processedAt->format('c');
                    break;
                case 'durationSecs':
                    $set['duration_secs'] = new RawExp(':durationSecs');
                    $values[$field] = $audioEntity->durationSecs;
                    break;
                case 'remoteFileUrl':
                    $set['remote_file_url'] = new RawExp(':remote_file_url');
                    $values[$field] = $audioEntity->remoteFileUrl;
                    break;
            }
        }

        $query->set($set);

        $stmt = $query->prepare();

        return $stmt->execute($values);
    }

    /**
     * Update the accessId (changes the base entities table)
     */
    public function updateAccessId(AudioEntity $audioEntity): bool
    {
        $query = $this->mysqlClientWriterHandler->update()
            ->set([
                'access_id' => new RawExp(':accessId')
            ])
            ->table(self::ENTITIES_TABLE_NAME)
            ->where('guid', Operator::EQ, new RawExp(':guid'))
            ->where('tenant_id', Operator::EQ, $this->getTenantId());
        
        $stmt = $query->prepare();

        return $stmt->execute([
            'guid' => $audioEntity->guid,
            'accessId' => $audioEntity->accessId,
        ]);
    }

    /**
     * Returns an audio entity by its guid
     */
    public function getByGuid(int $guid): ?AudioEntity
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(new RawExp(self::TABLE_NAME . ' as a'))
            ->joinRaw([ 'e' => self::ENTITIES_TABLE_NAME], 'e.tenant_id = a.tenant_id AND e.guid = a.guid')
            ->where('e.tenant_id', Operator::EQ, $this->getTenantId())
            ->where('e.guid', Operator::EQ, new RawExp(':guid'))
            ->prepare();

        $stmt->execute([
            'guid' => $guid,
        ]);

        $row = $stmt->fetchAll(PDO::FETCH_ASSOC)[0] ?? null;

        if (!$row) {
            return null;
        }

        return new AudioEntity(
            guid: $row['guid'],
            ownerGuid: $row['owner_guid'],
            accessId: $row['access_id'],
            durationSecs: (float) $row['duration_secs'],
            remoteFileUrl: $row['remote_file_url'],
            uploadedAt: isset($row['uploaded_at']) ? new DateTimeImmutable($row['uploaded_at']) : null,
        );
    }

    /**
     * Returns the tenant id
     */
    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
