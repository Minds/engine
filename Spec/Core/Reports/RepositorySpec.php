<?php

namespace Spec\Minds\Core\Reports;

use Minds\Core\Reports\Repository;
use Minds\Core\Reports\Report;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Data\ElasticSearch\Client;

class RepositorySpec extends ObjectBehavior
{
    private $es;

    function let(Client $es)
    {
        $this->beConstructedWith($es);
        $this->es = $es;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    function it_should_add_a_report(Report $report)
    {
        $ts = microtime(true);
        $this->es->request(Argument::that(function($prepared) use ($ts) {
                $query = $prepared->build();
                $params = $query['body']['script']['params']['report'];
                return $params[0]['reporter_guid'] === 456
                    && $params[0]['reason'] === 2
                    && $params[0]['@timestamp'] === $ts
                    && $query['body']['upsert']['entity_guid'] === 123
                    && $query['id'] === 123;
            }))
            ->shouldBeCalled()
            ->willReturn(true);

        $report->getTimestamp()
            ->shouldBeCalled()
            ->willReturn($ts);

        $report->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn(123);
        
        $report->getReporterGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $report->getReasonCode()
            ->shouldBeCalled()
            ->willReturn(2);

        $this->add($report)
            ->shouldBe(true);
    }

}
