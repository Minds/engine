<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Repositories;

use Minds\Core\Admin\Types\HashtagExclusion\HashtagExclusionNode;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Repository for managing hashtag exclusions.
 */
class HashtagExclusionRepository extends AbstractRepository
{
    /**
     * Upserts a tag into the database.
     * @param string $tag - The tag to upsert.
     * @param int $adminGuid - The admin GUID.
     * @return bool - True if the upsert was successful, false otherwise.
     * @throws ServerErrorException
     */
    public function upsertTag(string $tag, int $adminGuid): bool
    {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into('minds_admin_hashtag_exclusions')
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'tag' => new RawExp(':tag'),
                'admin_guid' => $adminGuid,
                'updated_timestamp' => new RawExp('CURRENT_TIMESTAMP'),
            ])
            ->onDuplicateKeyUpdate([
                'admin_guid' => $adminGuid,
                'updated_timestamp' => new RawExp('CURRENT_TIMESTAMP'),
            ])
            ->prepare();

        try {
            return $stmt->execute([
                'tag' => $tag,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to upsert admin hashtag exclusion',
                previous: $e
            );
        }
    }

    /**
     * Removes a tag from the database.
     * @param string $tag - The tag to remove.
     * @return bool - True if the removal was successful, false otherwise.
     * @throws ServerErrorException
     */
    public function removeTag(string $tag): bool
    {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from('minds_admin_hashtag_exclusions')
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('tag', Operator::EQ, new RawExp(':tag'))
            ->prepare();

        try {
            return $stmt->execute([
                'tag' => $tag,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to remove admin hashtag exclusion',
                previous: $e
            );
        }
    }

    /**
     * Get excluded tags from the database.
     * @param int|null $after - The timestamp after which to get the tags.
     * @param int|null $limit - The maximum number of tags to return.
     * @param bool &$hasNextPage - Whether there is a next page of results.
     * @return iterable<HashtagExclusionNode> - The excluded tags.
     * @throws ServerErrorException
     */
    public function getTags(?int $after = 0, ?int $limit = null, bool &$hasNextPage = false): iterable
    {
        $hasNextPage = false;
        $params = [];

        $stmt = $this->mysqlClientReaderHandler->select()
            ->from('minds_admin_hashtag_exclusions')
            ->columns([
                'tenant_id',
                'tag',
                'admin_guid',
                'created_timestamp',
                'updated_timestamp',
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->orderBy('created_timestamp DESC');

        if ($limit !== null) {
            $stmt->limit($limit + 1); // Fetch one extra item to check for next page
        }

        if ($after !== null) {
            $stmt->where('created_timestamp', Operator::LT, new RawExp(':after'));
            $params['after'] = date('c', $after);
        }

        $stmt = $stmt->prepare();

        try {
            $stmt->execute($params);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);

            $count = 0;
            foreach ($stmt->getIterator() as $row) {
                if ($limit !== null && $count >= $limit) {
                    $hasNextPage = true;
                    break;
                }

                yield new HashtagExclusionNode(
                    tenantId: (int) $row['tenant_id'],
                    tag: $row['tag'],
                    adminGuid: (int) $row['admin_guid'],
                    createdTimestamp: strtotime($row['created_timestamp']),
                    updatedTimestamp: strtotime($row['updated_timestamp'])
                );

                $count++;
            }
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to get admin hashtag exclusions',
                previous: $e
            );
        }
    }
}
