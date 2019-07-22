<?php
/**
 * Repository
 * @author edgebal
 */

namespace Minds\Core\Pro;

use Cassandra\Bigint;
use Cassandra\Rows;
use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;

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
    )
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = [])
    {
        $opts = array_merge([
            'user_guid' => null,
            'domain' => null,
            'limit' => null,
            'offset' => null,
        ], $opts);

        $cql = "SELECT * FROM pro";
        $where = [];
        $values = [];
        $cqlOpts = [];

        if ($opts['user_guid']) {
            $where .= 'user_guid = ?';
            $values[] = new Bigint($opts['user_guid']);
        } elseif ($opts['domain']) {
            $cql = "SELECT * FROM pro_by_domain";
            $where = 'domain = ?';
            $values[] = $opts['domain'];
        }

        if ($where) {
            $cql .= sprintf(" WHERE %s", implode(' AND ', $where));
        }

        if ($opts['limit']) {
            $cqlOpts['page_size'] = (int) $opts['limit'];
        }

        if ($opts['offset']) {
            $cqlOpts['paging_state_token'] = base64_decode($opts['offset']);
        }

        $prepared = new Custom();
        $prepared->query($cql, $values);
        $prepared->setOpts($cqlOpts);

        $response = new Response();

        try {
            /** @var Rows $rows */
            $rows = $this->db->request($prepared);

            if ($rows) {
                foreach ($rows as $row) {
                    $valuesEntity = new Values();
                    $valuesEntity
                        ->setUserGuid($row['user_guid']->toInt())
                        ->setDomain($row['domain']);

                    $response[] = $valuesEntity;
                }

                $response
                    ->setLastPage($rows->isLastPage())
                    ->setPagingToken(base64_encode($rows->pagingStateToken()));
            }
        } catch (Exception $e) {
            error_log("[ProRepository] $e");
            $response->setException($e);
        }

        return $response;
    }

    /**
     * @param Values $values
     * @return bool
     * @throws Exception
     */
    public function add(Values $values)
    {
        if (!$values->getUserGuid()) {
            throw new Exception('Invalid user GUID');
        }

        $cql = "INSERT INTO pro (user_guid, domain) VALUES (?, ?)";
        $values = [
            new Bigint($values->getUserGuid()),
            $values->getDomain(),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return (bool) $this->db->request($prepared, true);
    }

    /**
     * @param Values $values
     * @return bool
     * @throws Exception
     */
    public function update(Values $values)
    {
        return $this->add($values);
    }

    /**
     * @param Values $values
     * @return bool
     * @throws Exception
     */
    public function delete(Values $values)
    {
        if (!$values->getUserGuid()) {
            throw new Exception('Invalid user GUID');
        }

        $cql = "DELETE FROM pro WHERE user_guid = ?";
        $values = [
            new Bigint($values->getUserGuid()),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return (bool) $this->db->request($prepared, true);
    }
}
