<?php

namespace Spec\Minds\Core\Analytics\Dashboards\Timespans;

use Minds\Core\Analytics\Dashboards\Timespans\TimespansCollection;
use Minds\Core\Analytics\Dashboards\Timespans\TodayTimespan;
use Minds\Core\Analytics\Dashboards\Timespans\MtdTimespan;
use Minds\Core\Analytics\Dashboards\Timespans\YtdTimespan;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TimespansCollectionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(TimespansCollection::class);
    }

    public function it_should_add_timespans_to_collection()
    {
        $this->addTimespans(
            new TodayTimespan(),
            new MtdTimespan(),
            new YtdTimespan()
        );
        $this->getTimespans()['today']->getId()
            ->shouldBe('today');
        $this->getTimespans()['mtd']->getId()
            ->shouldBe('mtd');
        $this->getTimespans()['ytd']->getId()
            ->shouldBe('ytd');
    }

    public function it_should_export_timestamps()
    {
        $this->addTimespans(
            new TodayTimespan(),
            new MtdTimespan(),
            new YtdTimespan()
        );

        $exported = $this->export();
        $exported[0]['id']->shouldBe('today');
        $exported[0]['from_ts_ms']->shouldBe(strtotime('midnight') * 1000);
        $exported[1]['id']->shouldBe('mtd');
        $exported[1]['from_ts_ms']->shouldBe(strtotime('midnight first day of this month') * 1000);
        $exported[2]['id']->shouldBe('ytd');
        $exported[2]['from_ts_ms']->shouldBe(strtotime('midnight first day of January') * 1000);
        $exported[2]['interval']->shouldBe('month');
    }
}
