<?php
/**
 * Repository
 *
 * @author edgebal
 */

namespace Minds\Core\Features\Services\Unleash;

use Cassandra\Timestamp;
use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Helpers\Log;
use NotImplementedException;

class Repository
{
    /** @var Client */
    protected $db;

    /**
     * Repository constructor.
     * @param Client $db
     */
    public function __construct(
        $db = null
    ) {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Returns a list of all feature toggles cached in Cassandra
     * @return Response
     */
    public function getList(): Response
    {
        $cql = "SELECT * FROM feature_toggles_cache";

        $prepared = new Custom();
        $prepared->query($cql);

        $response = new Response();

        try {
            $rows = $this->db->request($prepared);

            foreach ($rows ?: [] as $row) {
                $entity = new Entity();
                $entity
                    ->setId($row['id'])
                    ->setData(json_decode($row['data'], true))
                    ->setCreatedAt($row['created_at']->time())
                    ->setStaleAt($row['stale_at']->time());
                $response[] = $entity;
            }
        } catch (Exception $e) {
            Log::warning($e);
        }

        return $response;
    }

    /**
     * Shortcut method that casts all the data from getList() entities
     * @return Response
     */
    public function getAllData(): Response
    {
        return $this->getList()->map(function (Entity $entity) {
            return $entity->getData();
        });
    }

    /**
     * Adds a new feature toggle entity to Cassandra
     * @param Entity $entity
     * @return bool
     * @throws Exception
     */
    public function add(Entity $entity): bool
    {
        if (!$entity->getId()) {
            throw new Exception('Invalid Unleash entity name');
        }

        $cql = "INSERT INTO feature_toggles_cache (id, data, created_at, stale_at) VALUES (?, ?, ?, ?)";
        $values = [
            (string) $entity->getId(),
            (string) json_encode($entity->getData()),
            new Timestamp($entity->getCreatedAt()),
            new Timestamp($entity->getStaleAt())
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        try {
            return (bool) $this->db->request($prepared, true);
        } catch (Exception $e) {
            Log::warning($e);
            return false;
        }
    }

    /**
     * Shortcut to add
     * @param Entity $entity
     * @return bool
     * @throws Exception
     */
    public function update(Entity $entity): bool
    {
        return $this->add($entity);
    }

    /**
     * Deletes an entity. Not implemented.
     * @param string $id
     * @return bool
     * @throws NotImplementedException
     */
    public function delete(string $id): bool
    {
        throw new NotImplementedException();
    }
}
