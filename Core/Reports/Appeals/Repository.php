<?php
namespace Minds\Core\Reports\Appeals;

use Cassandra\Bigint;
use Cassandra\Decimal;
use Cassandra\Map;
use Cassandra\Timestamp;
use Cassandra\Tinyint;
use Cassandra\Type;
use Minds\Common\Repository\Response;
use Minds\Core\Data;
use Minds\Core\Data\Cassandra\Prepared\Custom as Prepared;
use Minds\Core\Di\Di;
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
     * Return a list of appeals
     * @param array $options 'limit', 'offset', 'state'
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        $opts = array_merge([
            'limit' => 1200,
            'offset' => '',
            'state' => '',
            'owner_guid' => null,
            'showAppealed' => false,
        ], $opts);

        $statement = "SELECT * FROM moderation_reports_by_entity_owner_guid
            WHERE entity_owner_guid = ?";

        $values = [
            new Bigint($opts['owner_guid']),
        ];

        $prepared = new Prepared();
        $prepared->query($statement, $values);

        $results = $this->cql->request($prepared);

        $response = new Response();

        foreach ($results as $row) {
            $report = $this->reportsRepository->buildFromRow($row);

            // TODO: make this on the query level
            $skip = false;
            switch ($opts['state']) {
                case 'review':
                    if ($report->getState() != 'initial_jury_decided' || $report->isUpheld() === false) {
                        $skip = true;
                    }
                    break;
                case 'pending':
                    if ($report->getState() != 'appealed') {
                        $skip = true;
                    }
                    break;
                case 'approved':
                    if ($report->getState() !== 'appeal_jury_decided' || $report->isUpheld() === true) {
                        $skip = true;
                    }
                    break;
                case 'rejected':
                    if ($report->getState() !== 'appeal_jury_decided' || $report->isUpheld() === false) {
                        $skip = true;
                    }
                    break;
            }

            if ($skip) {
                continue;
            }

            $appeal = new Appeal;
            $appeal
                ->setTimestamp($report->getAppealTimestamp())
                ->setReport($report)
                ->setNote($report->getAppealNote());
            $response[] = $appeal;
        }
        return $response;
    }

    /**
     * Add an appeal
     * @param Appeal $appeal
     * @return boolean
     */
    public function add(Appeal $appeal)
    {
        $statement = "UPDATE moderation_reports
            SET appeal_note = ?,
            state = ?,
            state_changes += ?
            WHERE entity_urn = ?
            AND reason_code = ?
            AND sub_reason_code = ?
            AND timestamp = ?";

        $stateChanges = new Map(Type::text(), Type::timestamp());
        $stateChanges->set('appealed', new Timestamp($appeal->getTimestamp(), 0));

        $values = [
            $appeal->getNote(),
            'appealed',
            $stateChanges,
            $appeal->getReport()->getEntityUrn(),
            new Tinyint($appeal->getReport()->getInitialReasonCode()),
            new Decimal($appeal->getReport()->getInitialSubReasonCode()),
            new Timestamp($appeal->getReport()->getTimestamp(), 0),
        ];

        $prepared = new Prepared();
        $prepared->query($statement, $values);

        return (bool) $this->cql->request($prepared);
    }
}
