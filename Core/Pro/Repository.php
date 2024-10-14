<?php
/**
 * Repository.
 *
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
     *
     * @param Client $db
     */
    public function __construct(
        $db = null
    ) {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param array $opts
     *
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        $opts = array_merge([
            'user_guid' => null,
            'limit' => null,
            'offset' => null,
        ], $opts);

        $cql = 'SELECT * FROM pro';
        $where = [];
        $values = [];
        $cqlOpts = [];

        if ($opts['user_guid']) {
            $where[] = 'user_guid = ?';
            $values[] = new Bigint($opts['user_guid']);
        }

        if ($where) {
            $cql .= sprintf(' WHERE %s', implode(' AND ', $where));
        }

        if ($opts['limit']) {
            $cqlOpts['page_size'] = (int) $opts['limit'];
        }

        if ($opts['offset']) {
            $cqlOpts['paging_state_token'] = base64_decode($opts['offset'], true);
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
                    $settings = new Settings();
                    $settings
                        ->setUserGuid($row['user_guid']->toInt());

                    $data = json_decode($row['json_data'] ?: '{}', true);
                    $settings
                        ->setTimeUpdated($data['time_updated'] ?? 0)
                        ->setPayoutMethod($data['payout_method'] ?? 'usd');

                    $response[] = $settings;
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
     * @param Settings $settings
     *
     * @return bool
     *
     * @throws Exception
     */
    public function add(Settings $settings): bool
    {
        if (!$settings->getUserGuid()) {
            throw new Exception('Invalid user GUID');
        }

        $cql = 'INSERT INTO pro (user_guid, domain, json_data) VALUES (?, ?, ?)';
        $settings = [
            new Bigint($settings->getUserGuid()),
            null, // @deprecated domain column.
            json_encode([
                'user_guid' => (string) $settings->getUserGuid(),
                'time_updated' => $settings->getTimeUpdated(),
                'payout_method' => $settings->getPayoutMethod(),
            ]),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $settings);

        return (bool) $this->db->request($prepared, true);
    }

    /**
     * @param Settings $settings
     *
     * @return bool
     *
     * @throws Exception
     */
    public function update(Settings $settings): bool
    {
        return $this->add($settings);
    }

    /**
     * @param Settings $settingsRef
     *
     * @return bool
     *
     * @throws Exception
     */
    public function delete(Settings $settingsRef): bool
    {
        if (!$settingsRef->getUserGuid()) {
            throw new Exception('Invalid user GUID');
        }

        $cql = 'DELETE FROM pro WHERE user_guid = ?';
        $settingsRef = [
            new Bigint($settingsRef->getUserGuid()),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $settingsRef);

        return (bool) $this->db->request($prepared, true);
    }
}
