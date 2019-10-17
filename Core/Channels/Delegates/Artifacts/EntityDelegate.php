<?php
/**
 * EntityDelegate.
 *
 * @author emi
 */

namespace Minds\Core\Channels\Delegates\Artifacts;

use Minds\Core\Channels\Snapshots\Repository;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;

class EntityDelegate implements ArtifactsDelegateInterface
{
    /** @var Repository */
    protected $repository;

    /** @var CassandraClient */
    protected $db;

    /**
     * EntityDelegate constructor.
     * @param Repository $repository
     * @param CassandraClient $db
     */
    public function __construct(
        $repository = null,
        $db = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function snapshot($userGuid) : bool
    {
        return true;
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function restore($userGuid) : bool
    {
        return true;
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function hide($userGuid) : bool
    {
        return true;
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function delete($userGuid) : bool
    {
        $cql = "DELETE FROM entities WHERE key = ?";
        $values = [
            (string) $userGuid,
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        try {
            $this->db->request($prepared, true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string|int $userGuid
     * We just store owner guids in es, nothing to update here
     * @return bool
     */
    public function updateOwnerObject($userGuid, array $ownerObject) : bool
    {
        return true;
    }

    /**
    * @param string|int $guid
    * @return bool
    */
    protected function updateEntity($guid, string $column, string $value) : bool
    {
        $cql = "INSERT INTO entities (key, column1, value) VALUES (?, ?, ?)";
        $values = [
            (string) $guid,
            $col,
            $value,
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        try {
            $this->db->request($prepared, true);
        } catch (\Exception $e) {
            error_log((string) $e);
            return false;
        }

        return true;
    }
}
