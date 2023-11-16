<?php

namespace Spec\Minds\Core\Reports\V2\Services;

use Minds\Core\Guid;
use Minds\Core\Reports\Enums\ReportActionEnum;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use Minds\Core\Reports\Enums\ReportStatusEnum;
use Minds\Core\Reports\V2\Services\ActionService;
use Minds\Core\Reports\V2\Types\Report;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Minds\Core\Channels\Ban;
use Minds\Core\Comments\Comment;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\Comments\Manager as CommentManager;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class ActionServiceSpec extends ObjectBehavior
{
    protected Collaborator $entitiesBuilder;
    protected Collaborator $entitiesResolver;
    protected Collaborator $commentManager;
    protected Collaborator $channelsBanManager;
    protected Collaborator $deleteAction;

    public function let(
        EntitiesBuilder $entitiesBuilder,
        EntitiesResolver $entitiesResolver,
        CommentManager $commentManager,
        Ban $channelsBanManager,
        Delete $deleteAction,
    ) {
        $this->beConstructedWith(
            $entitiesBuilder,
            $entitiesResolver,
            $commentManager,
            $channelsBanManager,
            $deleteAction
        );

        $this->entitiesBuilder = $entitiesBuilder;
        $this->entitiesResolver = $entitiesResolver;
        $this->commentManager = $commentManager;
        $this->channelsBanManager = $channelsBanManager;
        $this->deleteAction = $deleteAction;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ActionService::class);
    }

    // handleReport: user

    public function it_should_handle_a_ban_report_for_a_user(
        User $user
    ) {
        $action = ReportActionEnum::BAN;
        $report = $this->generateMockReport(
            tenantId: 1234567890123456,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $this->entitiesResolver->single($report->entityUrn)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->channelsBanManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->channelsBanManager);

        $this->channelsBanManager->ban(implode('.', [ $report->reason->value, $report->getSubReason()?->value ]))
            ->shouldBeCalled();

        $this->handleReport($report, $action);
    }

    public function it_should_handle_a_delete_report_for_a_user(
        User $user
    ) {
        $action = ReportActionEnum::DELETE;
        $report = $this->generateMockReport(
            tenantId: 1234567890123456,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $this->entitiesResolver->single($report->entityUrn)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->deleteAction->delete()
            ->shouldNotBeCalled();

        $this->shouldThrow(GraphQLException::class)->duringHandleReport($report, $action);
    }

    // handleReport: group

    public function it_should_handle_a_ban_report_for_a_group(
        Group $entity,
        User $owner
    ) {
        $entityOwnerGuid = Guid::build();
        $action = ReportActionEnum::BAN;
        $report = $this->generateMockReport(
            tenantId: 1234567890123456,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($entityOwnerGuid);

        $this->entitiesResolver->single($report->entityUrn)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->entitiesBuilder->single($entityOwnerGuid)
            ->shouldBeCalled()
            ->willReturn($owner);

        $this->channelsBanManager->setUser($owner)
            ->shouldBeCalled()
            ->willReturn($this->channelsBanManager);

        $this->channelsBanManager->ban(implode('.', [ $report->reason->value, $report->getSubReason()?->value ]))
            ->shouldBeCalled();

        $this->handleReport($report, $action);
    }

    public function it_should_handle_a_delete_report_for_a_group(
        Group $entity
    ) {
        $action = ReportActionEnum::DELETE;
        $report = $this->generateMockReport(
            tenantId: 1234567890123456,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $this->entitiesResolver->single($report->entityUrn)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->deleteAction->setEntity($entity)
            ->shouldBeCalled()
            ->willReturn($this->deleteAction);

        $this->deleteAction->delete()
            ->shouldBeCalled();
        $this->handleReport($report, $action);
    }

    // handleReport: activity

    public function it_should_handle_a_ban_report_for_a_activity(
        Activity $entity,
        User $owner
    ) {
        $entityOwnerGuid = Guid::build();
        $action = ReportActionEnum::BAN;
        $report = $this->generateMockReport(
            tenantId: 1234567890123456,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($entityOwnerGuid);

        $this->entitiesResolver->single($report->entityUrn)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->entitiesBuilder->single($entityOwnerGuid)
            ->shouldBeCalled()
            ->willReturn($owner);

        $this->channelsBanManager->setUser($owner)
            ->shouldBeCalled()
            ->willReturn($this->channelsBanManager);

        $this->channelsBanManager->ban(implode('.', [ $report->reason->value, $report->getSubReason()?->value ]))
            ->shouldBeCalled();

        $this->handleReport($report, $action);
    }

    public function it_should_handle_a_delete_report_for_a_activity(
        Activity $entity
    ) {
        $action = ReportActionEnum::DELETE;
        $report = $this->generateMockReport(
            tenantId: 1234567890123456,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $this->entitiesResolver->single($report->entityUrn)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->deleteAction->setEntity($entity)
            ->shouldBeCalled()
            ->willReturn($this->deleteAction);

        $this->deleteAction->delete()
            ->shouldBeCalled();

        $this->handleReport($report, $action);
    }

    // handleReport: comment

    public function it_should_handle_a_ban_report_for_a_comment(
        Comment $entity,
        User $owner
    ) {
        $entityOwnerGuid = Guid::build();
        $action = ReportActionEnum::BAN;
        $report = $this->generateMockReport(
            tenantId: 1234567890123456,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($entityOwnerGuid);

        $this->entitiesResolver->single($report->entityUrn)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->entitiesBuilder->single($entityOwnerGuid)
            ->shouldBeCalled()
            ->willReturn($owner);

        $this->channelsBanManager->setUser($owner)
            ->shouldBeCalled()
            ->willReturn($this->channelsBanManager);

        $this->channelsBanManager->ban(implode('.', [ $report->reason->value, $report->getSubReason()?->value ]))
            ->shouldBeCalled();

        $this->handleReport($report, $action);
    }

    public function it_should_handle_a_delete_report_for_a_comment(
        Comment $entity
    ) {
        $action = ReportActionEnum::DELETE;
        $report = $this->generateMockReport(
            tenantId: 1234567890123456,
            status: ReportStatusEnum::PENDING,
            reason: ReportReasonEnum::SPAM,
        );

        $this->entitiesResolver->single($report->entityUrn)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->commentManager->delete($entity)
            ->shouldBeCalled();

        $this->handleReport($report, $action);
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
