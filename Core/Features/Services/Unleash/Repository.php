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
     * @param array $opts
     * @return Response
     * @throws Exception
     */
    public function getList(array $opts = []): Response
    {
        $opts = array_merge([
            'environment' => null,
        ], $opts);

        if (!$opts['environment']) {
            throw new Exception('Specify an environment');
        }

        $cql = "SELECT * FROM feature_toggles_cache_ns WHERE environment = ?";
        $values = [
            (string) $opts['environment']
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        $response = new Response();

        try {
            $rows = $this->db->request($prepared);

            foreach ($rows ?: [] as $row) {
                $entity = new Entity();
                $entity
                    ->setEnvironment($row['environment'])
                    ->setFeatureName($row['feature_name'])
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
     * @param array $opts getList() opts
     * @return Response
     * @throws Exception
     */
    public function getAllData(array $opts = []): Response
    {
        return $this
            ->getList($opts)
            ->map(function (Entity $entity) {
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
        if (!$entity->getEnvironment()) {
            throw new Exception('Invalid Unleash entity namespace');
        }

        if (!$entity->getFeatureName()) {
            throw new Exception('Invalid Unleash entity feature name');
        }

        $cql = "INSERT INTO feature_toggles_cache_ns (environment, feature_name, data, created_at, stale_at) VALUES (?, ?, ?, ?, ?)";
        $values = [
            (string) $entity->getEnvironment(),
            (string) $entity->getFeatureName(),
            (string) json_encode($entity->getData()),
            new Timestamp($entity->getCreatedAt(), 0),
            new Timestamp($entity->getStaleAt(), 0)
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
