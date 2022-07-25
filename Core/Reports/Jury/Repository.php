<?php
namespace Minds\Core\Reports\Jury;

use Cassandra\Bigint;
use Cassandra\Decimal;
use Cassandra\Map;
use Cassandra\Set;
use Cassandra\Timestamp;
use Cassandra\Tinyint;
use Cassandra\Type;
use Minds\Common\Repository\Response;
use Minds\Core\Data;
use Minds\Core\Data\Cassandra\Prepared\Custom as Prepared;
use Minds\Core\Di\Di;
use Minds\Core\Reports\Report;
use Minds\Core\Reports\Repository as ReportsRepository;

class Repository
{
    /** @var Data\Cassandra\Client $cql */
    protected $cql;

    /** @var ReportsRepository $reportsRepository */
    private $reportsRepository;

    /** @var Config $config */
    private $config;

    /** @var Logger $logger */
    private $logger;

    public function __construct($cql = null, $reportsRepository = null, $config = null)
    {
        $this->cql = $cql ?: Di::_()->get('Database\Cassandra\Cql');
        $this->reportsRepository = $reportsRepository ?: new ReportsRepository;
        $this->config = $config ?? Di::_()->get('Config');
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    /**
     * Return the decisions a jury has made
     * @param array $options 'limit', 'offset', 'state'
     * @return Response
     */
    public function getList(array $opts = [])
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => '',
            'state' => '',
            'owner' => null,
            'juryType' => 'appeal',
            'user' => null,
        ], $opts);

        if (!$opts['user']->getPhoneNumberHash()) {
            return null;
        }

        $statement = "SELECT * FROM moderation_reports
            WHERE state = ?";

        $values = [
            $opts['juryType'] === 'appeal' ? 'appealed' : 'reported',
        ];

        $prepared = new Prepared;

        $decodedPagingToken = base64_decode($opts['offset'], true) ?? '';

        $prepared->setOpts([
            'page_size' => (int) $opts['limit'],
            'paging_state_token' => $decodedPagingToken,
        ]);

        $prepared->query($statement, $values);

        $result = $this->cql->request($prepared);

        $response = new Response;

        foreach ($result as $row) {
            if ($row['user_hashes']
                && in_array(
                    $opts['user']->getPhoneNumberHash(),
                    array_map(function ($hash) {
                        return $hash;
                    }, $row['user_hashes']->values()),
                    true
                )
                && !($this->config->get('development_mode'))
            ) {
                continue; // Already interacted with
            }

            $report = $this->reportsRepository->buildFromRow($row);

            $response[] = $report;
        }

        try {
            if ($result) {
                $response->setPagingToken(urlencode(base64_encode($result->pagingStateToken())) ?? '');
                $response->setLastPage($result->isLastPage() ?? false);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $response;
    }

    /**
     * Return a single report
     * @param string $urn
     * @return Report
     */
    public function get($urn)
    {
        // TODO: Do not return if we no longer meet criteria
        return $this->reportsRepository->get($urn);
    }


    /**
     * Add a decision for a report
     * @param Decision $decision
     * @return boolean
     */
    public function add(Decision $decision)
    {
        $statement = "UPDATE moderation_reports
            SET initial_jury += ?,
            user_hashes += ?,
            admin_reason_override = ?
            WHERE entity_urn = ?
            AND reason_code = ?
            AND sub_reason_code = ?
            AND timestamp = ?";

        if ($decision->isAppeal()) {
            $statement = "UPDATE moderation_reports
                SET appeal_jury += ?,
                user_hashes += ?,
                admin_reason_override = ?
                WHERE entity_urn = ?
                AND reason_code = ?
                AND sub_reason_code = ?
                AND timestamp = ?";
        }

        $map = new Map(Type::bigint(), Type::boolean());
        $map->set(new Bigint($decision->getJurorGuid()), $decision->isUpheld());

        $set = new Set(Type::text());
        $set->add($decision->getJurorHash() ?? 'testing');
        $params = [
            $map,
            $set,
            $decision->getReport()->getAdminReasonOverride(),
            $decision->getReport()->getEntityUrn(),
            new Tinyint($decision->getReport()->getInitialReasonCode()),
            new Decimal($decision->getReport()->getInitialSubReasonCode()),
            new Timestamp($decision->getReport()->getTimestamp(), 0),
        ];

        $prepared = new Prepared();
        $prepared->query($statement, $params);

        return (bool) $this->cql->request($prepared);
    }

    /**
     * Count by jury type
     * @param array $options 'juryType'
     * @return Response
     */
    public function count(array $opts = []): int
    {
        $opts = array_merge([
            'juryType' => 'reported',
        ], $opts);

        $statement = "SELECT COUNT(*) FROM moderation_reports
            WHERE state = ?";

        $values = [
            $opts['juryType'] === 'appeal' ? 'appealed' : 'reported',
        ];

        $prepared = new Prepared;

        $prepared->query($statement, $values);

        $result = $this->cql->request($prepared);

        return (int) $result[0]['count'] ?? 0;
    }
}
