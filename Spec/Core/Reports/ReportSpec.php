<?php

namespace Spec\Minds\Core\Reports;

use Minds\Core\Reports\Report;
use Minds\Core\Reports\UserReports\UserReport;
use Minds\Entities\Entity;
use PhpSpec\ObjectBehavior;

class ReportSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Report::class);
    }

    public function it_should_return_a_urn()
    {
        $this->setEntityUrn('urn:activity:123')
            ->setReasonCode(2)
            ->setSubReasonCode(1)
            ->setTimestamp(1556898915000);

        $this->getUrn()
            ->shouldBe('urn:report:(urn:activity:123)-2-1-1556898915000');
    }

    public function it_should_export_for_non_admins(
        Entity $entity,
        UserReport $userReport1,
        UserReport $userReport2,
    ) {
        $entity->export()
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->setEntityUrn('~entity_urn~')
            ->setUrn('~urn~')
            ->setEntity($entity)
            ->setReports([$userReport1, $userReport2])
            ->setIsAppeal(false)
            ->setAppealNotice(false)
            ->setReasonCode(2)
            ->setSubReasonCode(1)
            ->setState('reported')
            ->setUpheld(false)
            ->setTimestamp(1556898915000);

        $this->export()->shouldBe([
            'urn' => 'urn:report:(~entity_urn~)-2-1-1556898915000',
            'entity_urn' => '~entity_urn~',
            'entity' => $entity,
            'reporting_users' => [], // no reporting users for non-admins.
            'reporting_users_count' => 2,
            'is_appeal' => false,
            'appeal_note' => "",
            'reason_code' => 2,
            'sub_reason_code' => 1,
            'admin_reason_override' => null,
            'state' => 'reported',
            'upheld' => null,
        ]);
    }
}
