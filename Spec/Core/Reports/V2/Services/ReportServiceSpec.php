<?php

namespace Spec\Minds\Core\Reports\V2\Services;

use Minds\Core\Config\Config;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Reports\Enums\ReportActionEnum;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use Minds\Core\Reports\Enums\ReportStatusEnum;
use Minds\Core\Reports\V2\Repositories\ReportRepository;
use Minds\Core\Reports\V2\Services\ActionService;
use Minds\Core\Reports\V2\Services\ReportService;
use Minds\Core\Reports\V2\Types\Report;
use Minds\Core\Reports\V2\Types\ReportsConnection;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ReportServiceSpec extends ObjectBehavior
{
    protected Collaborator $repository;
    protected Collaborator $actionService;
    protected Collaborator $config;
    protected Collaborator $logger;

    public function let(
        ReportRepository $repository,
        ActionService $actionService,
        Config $config,
        Logger $logger
    ) {
        $this->beConstructedWith(
            $repository,
            $actionService,
            $config,
            $logger
        );

        $this->repository = $repository;
        $this->actionService = $actionService;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ReportService::class);
    }

    // getReport

    public function it_should_get_a_report_with_status_with_status()
    {
        $tenantId = 1234567890123456;
        $reportGuid = 2234567890123456;
        $status = ReportStatusEnum::PENDING;
        $report = $this->generateMockReport(
            tenantId: $tenantId,
            status: $status,
            reason: ReportReasonEnum::SPAM,
        );

        $this->config->get('tenant_id')->willReturn($tenantId);

        $this->repository->getReport(
            tenantId: $tenantId,
            reportGuid: $reportGuid,
            status: $status
        )->shouldBeCalled()->willReturn($report);

        $this->getReport(
            reportGuid: $reportGuid,
            status: $status
        )->shouldBe($report);
    }

    public function it_should_get_a_report_with_null_status()
    {
        $tenantId = 1234567890123456;
        $reportGuid = 2234567890123456;
        $report = $this->generateMockReport(
            tenantId: $tenantId,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $this->config->get('tenant_id')->willReturn($tenantId);

        $this->repository->getReport(
            tenantId: $tenantId,
            reportGuid: $reportGuid,
            status: null
        )->shouldBeCalled()->willReturn($report);

        $this->getReport(
            reportGuid: $reportGuid,
        )->shouldBe($report);
    }

    // getReports

    public function it_should_get_reports(
    ) {
        $tenantId = 1234567890123456;
        $limit = 12;
        $loadAfter = null;
        $status = ReportStatusEnum::PENDING;
        $report1 = $this->generateMockReport(
            tenantId: $tenantId,
            status: $status,
            reason: ReportReasonEnum::SPAM,
        );
        $report2 = $this->generateMockReport(
            tenantId: $tenantId,
            status: $status,
            reason: ReportReasonEnum::SPAM,
        );
        
        $this->config->get('tenant_id')->willReturn($tenantId);

        $this->repository->getReports(
            tenantId: $tenantId,
            status: $status,
            limit: $limit,
            loadAfter: $loadAfter,
            hasMore: false
        )
            ->shouldBeCalled()
            ->willYield([
                $report1, $report2
            ]);

        $this->getReports(
            limit: $limit,
            loadAfter: $loadAfter,
            status: $status
        )->shouldHaveType(ReportsConnection::class);
    }

    // createNewReport

    public function it_should_create_a_new_report()
    {
        $tenantId = 1234567890123456;
        $entityUrn = 'urn:activity:123';
        $reportedByGuid = 2234567890123456;
        $reason = ReportReasonEnum::SPAM;
        $subReason = null;

        $this->config->get('tenant_id')->willReturn($tenantId);

        $this->repository->createNewReport(
            tenantId: $tenantId,
            entityGuid: 123,
            entityUrn: $entityUrn,
            reportedByGuid: $reportedByGuid,
            reason: $reason,
            subReason: $subReason
        )->shouldBeCalled()->willReturn(true);

        $this->createNewReport(
            entityUrn: $entityUrn,
            reportedByGuid: $reportedByGuid,
            reason: $reason,
            subReason: $subReason
        )->shouldBe(true);
    }

    // provideVerdict

    public function it_should_provide_a_verdict()
    {
        $tenantId = 1234567890123456;
        $reportGuid = 2234567890123456;
        $moderatedByGuid = 3234567890123456;
        $action = ReportActionEnum::BAN;

        $this->config->get('tenant_id')->willReturn($tenantId);

        $report = $this->generateMockReport(
            tenantId: $tenantId,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $this->repository->getReport(
            tenantId: $tenantId,
            reportGuid: $reportGuid,
            status: ReportStatusEnum::PENDING
        )->shouldBeCalled()->willReturn($report);

        $this->actionService->handleReport($report, $action)
            ->shouldBeCalled();

        $this->repository->updateWithVerdict(
            tenantId: $tenantId,
            entityGuid: $report->entityGuid,
            moderatedByGuid: $moderatedByGuid,
            action: $action,
            reason: $report->reason,
            subReason: $report->getSubReason()
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->provideVerdict(
            reportGuid: $reportGuid,
            moderatedByGuid: $moderatedByGuid,
            action: $action
        )->shouldBe(true);
    }

    // utils

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
