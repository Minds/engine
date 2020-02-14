<?php

namespace Spec\Minds\Core\Reports\UserReports;

use Minds\Core\Reports\UserReports\Repository;
use Minds\Core\Reports\UserReports\UserReport;
use Minds\Core\Reports\Report;
use Minds\Core\Data\Cassandra\Client;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private $cql;

    public function let(Client $cql)
    {
        $this->beConstructedWith($cql);
        $this->cql = $cql;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add_a_report(UserReport $userReport, Activity $activity)
    {
        $ts = (int) microtime(true);
        $this->cql->request(Argument::that(function ($prepared) use ($ts) {
            $query = $prepared->build();
            $values = $query['values'];

            return $values[0]->values()[0]->value() == 456
                && $values[2] == "{}"
                && $values[3]->values()[0] == 'hash'
                && $values[4] === 'urn:activity:123'
                && $values[5]->value() == 2
                && $values[6]->value() == 4
                && $values[7]->time() == $ts;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $userReport->getReport()
            ->shouldBeCalled()
            ->willReturn(
                (new Report)
                    ->setEntityUrn("urn:activity:123")
                    ->setEntity($activity)
                    ->setEntityOwnerGuid(1)
                    ->setTimestamp($ts)
                    ->setReasonCode(2)
                    ->setSubReasonCode(4)
            );

        $userReport->getReporterGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $userReport->getReporterHash()
            ->shouldBeCalled()
            ->willReturn('hash');

        $this->add($userReport)
            ->shouldBe(true);
    }
}
