<?php

namespace Spec\Minds\Core\Reports\V2\Repositories;

use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Guid;
use Minds\Core\Reports\Enums\Reasons\Illegal\SubReasonEnum as IllegalSubReasonEnum;
use Minds\Core\Reports\Enums\ReportActionEnum;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use Minds\Core\Reports\Enums\ReportStatusEnum;
use Minds\Core\Reports\V2\Repositories\ReportRepository;
use Minds\Core\Reports\V2\Types\Report;
use PDOStatement;
use PDO;

class ReportRepositorySpec extends ObjectBehavior
{
    private $mysqlClientMock;
    private $mysqlMasterMock;
    private $mysqlReplicaMock;

    public function let(MySQLClient $mysqlClient, PDO $mysqlMasterMock, PDO $mysqlReplicaMock)
    {
        $this->beConstructedWith($mysqlClient, Di::_()->get(Config::class), Di::_()->get('Logger'));
        $this->mysqlClientMock = $mysqlClient;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(ReportRepository::class);
    }

    // getReport

    public function it_can_get_a_report_filtered_by_a_status(
        PDOStatement $pdoStatementMock
    ) {
        $report = $this->generateMockReport(
            tenantId: 123,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM
        );
        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);
        
        $pdoStatementMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'tenant_id' => $report->tenantId,
                    'report_guid' => $report->reportGuid,
                    'entity_guid' =>  $report->entityGuid,
                    'entity_urn' => $report->entityUrn,
                    'reported_by_guid' => $report->reportedByGuid,
                    'moderated_by_guid' => $report->moderatedByGuid,
                    'created_timestamp' => date('c', $report->createdTimestamp),
                    'status' => $report->status?->value,
                    'action' => $report->action?->value,
                    'reason' => $report->reason?->value,
                    'sub_reason' => $report->nsfwSubReason?->value,
                ]
            ]);

        $this->mysqlClientMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => $report->tenantId,
            'report_guid' => $report->reportGuid,
            'status' => $report->status->value
        ])->shouldBeCalled();

        $result = $this->getReport(
            tenantId: $report->tenantId,
            reportGuid: $report->reportGuid,
            status: $report->status
        );

        $result->shouldBeLike($report);
    }

    public function it_can_get_a_report_not_filtered_by_status(
        PDOStatement $pdoStatementMock
    ) {
        $report = $this->generateMockReport(
            tenantId: 123,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM
        );
        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);
        
        $pdoStatementMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'tenant_id' => $report->tenantId,
                    'report_guid' => $report->reportGuid,
                    'entity_guid' =>  $report->entityGuid,
                    'entity_urn' => $report->entityUrn,
                    'reported_by_guid' => $report->reportedByGuid,
                    'moderated_by_guid' => $report->moderatedByGuid,
                    'created_timestamp' => date('c', $report->createdTimestamp),
                    'status' => $report->status?->value,
                    'action' => $report->action?->value,
                    'reason' => $report->reason?->value,
                    'sub_reason' => $report->nsfwSubReason?->value,
                ]
            ]);

        $this->mysqlClientMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => $report->tenantId,
            'report_guid' => $report->reportGuid,
        ])->shouldBeCalled();

        $result = $this->getReport(
            tenantId: $report->tenantId,
            reportGuid: $report->reportGuid,
            status: null
        );

        $result->shouldBeLike($report);
    }

    // getReports

    public function it_can_get_reports(
        PDOStatement $pdoStatementMock
    ) {
        $status = ReportStatusEnum::PENDING;
        $limit = 12;

        $report1 = $this->generateMockReport(
            tenantId: 123,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM
        );
        $report2 = $this->generateMockReport(
            tenantId: 123,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::IMPERSONATION
        );

        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);
        
        $pdoStatementMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'tenant_id' => $report1->tenantId,
                    'report_guid' => $report1->reportGuid,
                    'entity_guid' =>  $report1->entityGuid,
                    'entity_urn' => $report1->entityUrn,
                    'reported_by_guid' => $report1->reportedByGuid,
                    'moderated_by_guid' => $report1->moderatedByGuid,
                    'created_timestamp' => date('c', $report1->createdTimestamp),
                    'status' => $report1->status?->value,
                    'action' => $report1->action?->value,
                    'reason' => $report1->reason?->value,
                ],
                [
                    'tenant_id' => $report2->tenantId,
                    'report_guid' => $report2->reportGuid,
                    'entity_guid' =>  $report2->entityGuid,
                    'entity_urn' => $report2->entityUrn,
                    'reported_by_guid' => $report2->reportedByGuid,
                    'moderated_by_guid' => $report2->moderatedByGuid,
                    'created_timestamp' => date('c', $report2->createdTimestamp),
                    'status' => $report2->status?->value,
                    'action' => $report2->action?->value,
                    'reason' => $report2->reason?->value,
                ]
            ]);

        $this->mysqlClientMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => $report1->tenantId,
            'status' => $status->value,
        ])->shouldBeCalled();

        $result = $this->getReports(
            tenantId: 123,
            status: $status
        );

        $result->shouldYieldLike(new \ArrayIterator([
            $report1, $report2
        ]));
    }

    // createNewReport

    public function it_should_create_a_new_report(
        PDOStatement $pdoStatementMock,
    ) {
        $tenantId = 123;
        $entityGuid = (int) Guid::build();
        $entityUrn = 'urn:activity:'.Guid::build();
        $reportedByGuid = (int) Guid::build();
        $reason = ReportReasonEnum::IMPERSONATION;
        $subReason = null;

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            Argument::that(function ($args) use (
                $tenantId,
                $entityGuid,
                $entityUrn,
                $reportedByGuid,
                $reason,
                $subReason
            ) {
                $isNumericReportGuid = is_numeric($args['report_guid']);
                unset($args['report_guid']);
                return $isNumericReportGuid && $args === [
                    'tenant_id' => $tenantId,
                    'entity_guid' => $entityGuid,
                    'entity_urn' => $entityUrn,
                    'reported_by_guid' => $reportedByGuid,
                    'reason' => $reason->value,
                    'sub_reason' => $subReason?->value,
                    'status' => ReportStatusEnum::PENDING->value
                ];
            })
        )->shouldBeCalled();

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->createNewReport(
            tenantId: $tenantId,
            entityGuid: $entityGuid,
            entityUrn: $entityUrn,
            reportedByGuid: $reportedByGuid,
            reason: $reason,
            subReason: $subReason
        )->shouldBe(true);
    }

    // updateWithVerdict

    public function it_should_update_a_report_with_a_verdict(
        PDOStatement $pdoStatementMock,
    ) {
        $tenantId = 123;
        $entityGuid = (int) Guid::build();
        $moderatedByGuid = (int) Guid::build();
        $action = ReportActionEnum::BAN;
        $reason = ReportReasonEnum::IMPERSONATION;
        $subReason = IllegalSubReasonEnum::EXTORTION;

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            Argument::that(function ($args) use (
                $tenantId,
                $entityGuid,
                $moderatedByGuid,
                $action,
                $reason,
                $subReason
            ) {
                return $args = [
                    'tenant_id' => $tenantId,
                    'entity_guid' => $entityGuid,
                    'moderated_by_guid' => $moderatedByGuid,
                    'action' => $action,
                    'new_status' => ReportStatusEnum::ACTIONED->value,
                    'old_status' => ReportStatusEnum::PENDING->value,
                    'reason' => $reason,
                    'sub_reason' => $subReason,
                ];
            })
        )->shouldBeCalled();

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->updateWithVerdict(
            tenantId: $tenantId,
            entityGuid: $entityGuid,
            moderatedByGuid: $moderatedByGuid,
            action: $action,
            reason: $reason,
            subReason: $subReason
        )->shouldBe(true);
    }

    private function generateMockReport(
        int $tenantId,
        ReportStatusEnum $status,
        ReportReasonEnum $reason
    ): Report {
        $entityGuid = Guid::build();
        return new Report(
            tenantId: $tenantId,
            reportGuid: Guid::build(),
            entityGuid: $entityGuid,
            entityUrn: "urn:activity:$entityGuid",
            reportedByGuid: Guid::build(),
            moderatedByGuid: Guid::build(),
            createdTimestamp: time(),
            status: $status,
            action: null,
            reason: $reason,
            illegalSubReason: null,
            nsfwSubReason: null,
            securitySubReason: null,
            updatedTimestamp: null,
            cursor: ''
        );
    }
}
