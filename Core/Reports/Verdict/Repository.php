<?php
namespace Minds\Core\Reports\Verdict;

use Cassandra\Decimal;
use Cassandra\Map;
use Cassandra\Timestamp;
use Cassandra\Tinyint;
use Cassandra\Type;
use Minds\Common\Repository\Response;
use Minds\Core\Data;
use Minds\Core\Data\Cassandra\Prepared\Custom as Prepared;
use Minds\Core\Di\Di;
use Minds\Core\Reports\Jury\Decision;
use Minds\Core\Reports\Repository as ReportsRepository;

class Repository
{
    /** @var Data\Cassandra\Client $cql */
    protected $cql;

    /** @var ReportsRepository $reportsRepository */
    private $reportsRepository;

    public function __construct($cql = null, $reportsRepository = null)
    {
        $this->cql = $cql ?: Di::_()->get('Database\Cassandra\Cql');
        $this->reportsRepository = $reportsRepository ?: new ReportsRepository;
    }

    /**
     * Return the decisions a jury has made
     * @param array $options 'limit', 'offset', 'state'
     * @return array
     */
    public function getList(array $opts = [])
    {
        return new Response;
    }

    /**
     * @param $entity_guid
     * @return Verdict
     */
    public function get($entity_guid)
    {
        return null;
    }

    /**
     * Add a decision for a report
     * @param Decision $decision
     * @return boolean
     */
    public function add(Verdict $verdict)
    {
        $statement = "UPDATE moderation_reports
            SET state = ?,
              state_changes += ?,
              uphold = ?,
              admin_reason_override = ?
            WHERE entity_urn = ?
            AND reason_code = ?
            AND sub_reason_code = ?
            AND timestamp = ?";

        $state = $verdict->isAppeal() ? 'appeal_jury_decided' : 'initial_jury_decided';
            
        $stateChangesMap = new Map(Type::text(), Type::timestamp());
        $stateChangesMap->set($state, new Timestamp(microtime(true), 0));

        $values = [
            $state,
            $stateChangesMap,
            (bool) $verdict->isUpheld(),
            $verdict->getReport()->getAdminReasonOverride(),
            $verdict->getReport()->getEntityUrn(),
            new Tinyint($verdict->getReport()->getInitialReasonCode()),
            new Decimal($verdict->getReport()->getInitialSubReasonCode()),
            new Timestamp($verdict->getReport()->getTimestamp(), 0),
        ];

        $prepared = new Prepared;
        $prepared->query($statement, $values);

        return (bool) $this->cql->request($prepared);
    }
}
