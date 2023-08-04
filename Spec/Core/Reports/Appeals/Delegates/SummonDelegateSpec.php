<?php

namespace Spec\Minds\Core\Reports\Appeals\Delegates;

use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Core\Queue\Runners\ReportsAppealSummon;
use Minds\Core\Reports\Appeals\Appeal;
use Minds\Core\Reports\Appeals\Delegates\SummonDelegate;
use Minds\Core\Reports\Report;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SummonDelegateSpec extends ObjectBehavior
{
    /** @var QueueClient */
    protected $queue;

    public function let(
        QueueClient $queue
    ) {
        $this->beConstructedWith($queue);
        $this->queue = $queue;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SummonDelegate::class);
    }

    public function it_should_queue_on_appeal(Report $report)
    {
        $report = new Report();
        $report->setReasonCode(2);

        $appeal = new Appeal();
        $appeal->setReport($report);

        $this->queue->setQueue('ReportsAppealSummon')
            ->shouldBeCalled()
            ->willReturn($this->queue);

        $this->queue->send(Argument::any())
            ->shouldBeCalled();

        $this
            ->shouldNotThrow()
            ->duringOnAppeal($appeal);
    }
}
