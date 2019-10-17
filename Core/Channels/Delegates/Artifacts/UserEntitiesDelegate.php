<?php
/**
 * UserEntitiesDelegate.
 *
 * @author emi
 */

namespace Minds\Core\Channels\Delegates\Artifacts;

use Minds\Core\Channels\Snapshots\Repository;
use Minds\Core\Channels\Snapshots\Snapshot;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Data\Cassandra\Scroll as CassandraScroll;
use Minds\Core\Di\Di;

class UserEntitiesDelegate implements ArtifactsDelegateInterface
{
    /** @var Repository */
    protected $repository;

    /** @var CassandraClient */
    protected $db;

    /** @var CassandraScroll */
    protected $scroll;

    /** @var string[] */
    const TABLE_KEYS = [
        'activity:user:%s',
        'activity:user:%s:hidden',
        'object:blog:user:%s',
        'object:blog:user:%s:hidden',
        'object:image:user:%s',
        'object:image:user:%s:hidden',
        'object:video:user:%s',
        'object:video:user:%s:hidden',
    ];

    /**
     * UserEntitiesDelegate constructor.
     * @param Repository $repository
     * @param CassandraClient $db
     * @param CassandraScroll $scroll
     */
    public function __construct(
        $repository = null,
        $db = null,
        $scroll = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
        $this->scroll = $scroll ?: Di::_()->get('Database\Cassandra\Cql\Scroll');
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
     * @throws \Exception
     */
    public function restore($userGuid) : bool
    {
        foreach (static::TABLE_KEYS as $tableKey) {
            $key = sprintf($tableKey, $userGuid);

            try {
                $cql = "SELECT * FROM entities_by_time WHERE key = ?";
                $values = [
                    $key,
                ];

                $prepared = new Custom();
                $prepared->query($cql, $values);

                $rows = $this->scroll->request($prepared);

                foreach ($rows as $row) {
                    $this->restoreEntity($row['column1']);
                }
            } catch (\Exception $e) {
                error_log((string) $e);
            }
        }

        return true;
    }

    /**
     * @param string|int user guid to be update
     * Loops through a user's history and updates a field with a value
     */
    public function updateOwnerObject($userGuid, array $ownerObject) : bool
    {
        foreach (static::TABLE_KEYS as $tableKey) {
            $key = sprintf($tableKey, $userGuid);

            try {
                $cql = "SELECT * FROM entities_by_time WHERE key = ?";
                $values = [
                    $key,
                ];

                $prepared = new Custom();
                $prepared->query($cql, $values);

                $rows = $this->scroll->request($prepared);

                foreach ($rows as $row) {
                    $this->updateEntity($row['column1'], 'owner_obj', json_encode($ownerObject));
                }
            } catch (\Exception $e) {
                error_log((string) $e);
            }
        }

        return true;
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function hide($userGuid) : bool
    {
        foreach (static::TABLE_KEYS as $tableKey) {
            $key = sprintf($tableKey, $userGuid);

            try {
                $cql = "SELECT * FROM entities_by_time WHERE key = ?";
                $values = [
                    $key,
                ];

                $prepared = new Custom();
                $prepared->query($cql, $values);

                $rows = $this->scroll->request($prepared);

                foreach ($rows as $row) {
                    $this->hideEntity($row['column1']);
                }
            } catch (\Exception $e) {
                error_log((string) $e);
            }
        }

        return true;
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function delete($userGuid) : bool
    {
        foreach (static::TABLE_KEYS as $tableKey) {
            $key = sprintf($tableKey, $userGuid);

            try {
                $cql = "SELECT * FROM entities_by_time WHERE key = ?";
                $values = [
                    $key,
                ];

                $prepared = new Custom();
                $prepared->query($cql, $values);

                $rows = $this->scroll->request($prepared);

                foreach ($rows as $row) {
                    $this->deleteEntity($row['column1']);
                }

                $cql = "DELETE FROM entities_by_time WHERE key = ?";
                $values = [
                    $key,
                ];

                $prepared = new Custom();
                $prepared->query($cql, $values);

                $this->db->request($prepared, true);
            } catch (\Exception $e) {
                error_log((string) $e);
            }
        }

        return true;
    }

    /**
     * @param string|int $guid
     * @return bool
     */
    protected function restoreEntity($guid) : bool
    {
        $cql = "DELETE FROM entities WHERE key = ? AND column1 = ?";
        $values = [
            (string) $guid,
            'deleted',
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

    /**
     * @param string|int $guid
     * @return bool
     */
    protected function hideEntity($guid) : bool
    {
        $cql = "INSERT INTO entities (key, column1, value) VALUES (?, ?, ?)";
        $values = [
            (string) $guid,
            'deleted',
            '1',
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

    /**
     * @param string|int $guid
     * @return bool
     */
    protected function deleteEntity($guid) : bool
    {
        $cql = "DELETE FROM entities WHERE key = ?";
        $values = [
            (string) $guid,
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

    /**
    * @param string|int $guid
    * @return bool
    */
    protected function updateEntity($guid, string $column, string $value) : bool
    {
        $cql = "INSERT INTO entities (key, column1, value) VALUES (?, ?, ?)";
        $values = [
            (string) $guid,
            $column,
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
