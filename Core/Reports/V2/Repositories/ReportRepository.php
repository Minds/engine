<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Guid;
use Minds\Core\Reports\Enums\Reasons\Illegal\SubReasonEnum as IllegalSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Nsfw\SubReasonEnum as NsfwSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Security\SubReasonEnum as SecuritySubReasonEnum;
use Minds\Core\Reports\Enums\ReportActionEnum;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use Minds\Core\Reports\Enums\ReportStatusEnum;
use Minds\Core\Reports\V2\Types\Report;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Repository for the handling of V2 reports.
 */
class ReportRepository extends AbstractRepository
{
    /**
     * Get a single report.
     * @param int $tenantId - id of the tenant.
     * @param int $reportGuid - guid of the report.
     * @param ReportStatusEnum|null $status - status of the report. Allows checking whether a report is in a given status.
     * @return Report|null - the report.
     */
    public function getReport(
        int $tenantId,
        int $reportGuid,
        ?ReportStatusEnum $status = null
    ): ?Report {
        $values = [
            'tenant_id' => $tenantId,
            'report_guid' => $reportGuid
        ];

        $query = $this->mysqlClientReaderHandler
            ->select()
            ->from('minds_reports')
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('report_guid', Operator::EQ, new RawExp(':report_guid'));

        if (isset($status)) {
            $query->where('status', Operator::EQ, new RawExp(':status'));
            $values['status'] = $status->value;
        }

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return isset($rows[0]) ? $this->buildReport($rows[0]) : null;
    }

    /**
     * Gets an iterable of reports.
     * @param int $tenantId - id of the tenant.
     * @param ReportStatusEnum|null $status - status of the reports.
     * @param int $limit - limit of reports to load.
     * @param int $loadAfter - load after cursor - passed by reference, will be updated
     * to indicate the next cursor for pagination.
     * @param bool|null $hasMore - passed by reference, will be updated to indicate
     * whether there are more pages to load.
     * @return iterable<Report> - iterable of reports.
     */
    public function getReports(
        int $tenantId,
        ReportStatusEnum $status = null,
        int $limit = 12,
        int &$loadAfter = null,
        ?bool &$hasMore = null
    ): ?iterable {
        $values = [ 'tenant_id' => $tenantId ];

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'report_guid' => new RawExp("MIN(report_guid)"),
                'tenant_id',
                'entity_guid',
                'entity_urn' => new RawExp("MIN(entity_urn)"),
                'reported_by_guid' => new RawExp("MIN(reported_by_guid)"),
                'moderated_by_guid' => new RawExp("MIN(moderated_by_guid)"),
                'reason',
                'sub_reason',
                'status' => new RawExp("MIN(status)"),
                'action' => new RawExp("MIN(action)"),
                'created_timestamp' => new RawExp("MAX(created_timestamp)"),
                'updated_timestamp' => new RawExp("MIN(updated_timestamp)"),
            ])
            ->from('minds_reports')
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        if ($loadAfter) {
            $query->where('created_timestamp', Operator::GT, date('c', $loadAfter));
        }

        if (isset($status)) {
            $query->where('status', Operator::EQ, new RawExp(':status'));
            $values['status'] = $status->value;
        }

        $query->orderBy('created_timestamp ASC')
            ->groupBy('entity_guid', 'reason', 'sub_reason')
            ->limit($limit + 1);

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $i => $row) {
            if ($i === $limit) {
                $hasMore = true;
                $loadAfter = strtotime($rows[$i - 1]['created_timestamp']);
                break;
            }

            yield $this->buildReport($row);
        }
    }

    /**
     * Create a new report.
     * @param int $tenantId - id of the tenant.
     * @param int $entityGuid - guid of the entity.
     * @param string $entityUrn - urn of the entity.
     * @param int $reportedByGuid - guid of the user who reported the entity.
     * @param ReportReasonEnum $reason - reason for the report.
     * @param IllegalSubReasonEnum|NsfwSubReasonEnum|SecuritySubReasonEnum|null $subReason - sub reason for the report.
     * @return bool true on success.
     */
    public function createNewReport(
        int $tenantId,
        int $entityGuid,
        string $entityUrn,
        int $reportedByGuid,
        ReportReasonEnum $reason,
        IllegalSubReasonEnum|NsfwSubReasonEnum|SecuritySubReasonEnum|null $subReason = null,
    ): bool {
        $query = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_reports')
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'report_guid' => new RawExp(':report_guid'),
                'entity_guid' => new RawExp(':entity_guid'),
                'entity_urn' => new RawExp(':entity_urn'),
                'reported_by_guid' => new RawExp(':reported_by_guid'),
                'reason' => new RawExp(':reason'),
                'sub_reason' => new RawExp(':sub_reason'),
                'status' => new RawExp(':status')
            ]);

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'tenant_id' => $tenantId,
            'report_guid' => Guid::build(),
            'entity_guid' => $entityGuid,
            'entity_urn' => $entityUrn,
            'reported_by_guid' => $reportedByGuid,
            'reason' => $reason->value,
            'sub_reason' => $subReason?->value ?? null,
            'status' => ReportStatusEnum::PENDING->value
        ]);

        return $statement->execute();
    }

    /**
     * Update ALL pending status rows for a verdict with matching entity_guid, reason and subreasons.
     * @param int $tenantId - id of the tenant.
     * @param int $entityGuid - guid of the entity.
     * @param int $moderatedByGuid - guid of the user who moderated the report.
     * @param ReportActionEnum $action - action taken on the report.
     * @param ReportReasonEnum $reason - reason for the report.
     * @param IllegalSubReasonEnum|NsfwSubReasonEnum|SecuritySubReasonEnum|null $subReason - sub reason for the report.
     * @return bool true on success.
     */
    public function updateWithVerdict(
        int $tenantId,
        int $entityGuid,
        int $moderatedByGuid,
        ReportActionEnum $action,
        ReportReasonEnum $reason,
        IllegalSubReasonEnum|NsfwSubReasonEnum|SecuritySubReasonEnum|null $subReason = null,
    ): bool {
        $values = [
            'tenant_id' => $tenantId,
            'entity_guid' => $entityGuid,
            'moderated_by_guid' => $moderatedByGuid,
            'action' => $action->value,
            'new_status' => ReportStatusEnum::ACTIONED->value,
            'old_status' => ReportStatusEnum::PENDING->value,
            'reason' => $reason->value,
            'sub_reason' => $subReason?->value ?? null,
        ];

        $query = $this->mysqlClientWriterHandler
            ->update()
            ->table('minds_reports')
            ->set([
                'moderated_by_guid' => new RawExp(':moderated_by_guid'),
                'action' => new RawExp(':action'),
                'status' => new RawExp(':new_status')
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('entity_guid', Operator::EQ, new RawExp(':entity_guid'))
            ->where('reason', Operator::EQ, new RawExp(':reason'))
            ->where('status', Operator::EQ, new RawExp(':old_status'));

        if (isset($subReason)) {
            $query->where('sub_reason', Operator::EQ, new RawExp(':sub_reason'));
        } else {
            $query->where('sub_reason', Operator::IS, new RawExp(':sub_reason'));
        }

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement->execute();
    }

    /**
     * Build a report from a row.
     * @param array $row - row from the database.
     * @return Report - the report.
     */
    private function buildReport(array $row): Report
    {
        $reasonEnum = ReportReasonEnum::tryFrom($row['reason']);
        $illegalSubReason = null;
        $nsfwSubReason = null;
        $securitySubReason = null;

        if (isset($row['sub_reason'])) {
            switch ($reasonEnum) {
                case ReportReasonEnum::ILLEGAL:
                    $illegalSubReason = IllegalSubReasonEnum::tryFrom($row['sub_reason']);
                    break;
                case ReportReasonEnum::NSFW:
                    $nsfwSubReason = NsfwSubReasonEnum::tryFrom($row['sub_reason']);
                    break;
                case ReportReasonEnum::SECURITY:
                    $securitySubReason = SecuritySubReasonEnum::tryFrom($row['sub_reason']);
                    break;
            }
        }

        return new Report(
            tenantId: $row['tenant_id'],
            reportGuid: $row['report_guid'],
            entityGuid: $row['entity_guid'],
            entityUrn: $row['entity_urn'],
            reportedByGuid: $row['reported_by_guid'],
            moderatedByGuid: $row['moderated_by_guid'] ?? null,
            createdTimestamp: strtotime($row['created_timestamp']),
            status: ReportStatusEnum::tryFrom($row['status']),
            action: isset($row['action']) ? ReportActionEnum::tryFrom($row['action']) : null,
            reason: ReportReasonEnum::tryFrom($row['reason']),
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason,
            updatedTimestamp: isset($row['updated_timestamp']) ? strtotime($row['updated_timestamp']) : null
        );
    }
}
