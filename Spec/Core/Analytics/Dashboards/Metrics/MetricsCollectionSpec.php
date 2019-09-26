<?php

namespace Spec\Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Analytics\Dashboards\Metrics\MetricsCollection;
use Minds\Core\Analytics\Dashboards\Metrics\ActiveUsersMetric;
use Minds\Core\Analytics\Dashboards\Metrics\SignupsMetric;
use Minds\Core\Analytics\Dashboards\Metrics\ViewsMetric;
use Minds\Core\Analytics\Dashboards\Timespans\TimespansCollection;
use Minds\Core\Analytics\Dashboards\Filters\FiltersCollection;
use Minds\Core\Analytics\Dashboards\Timespans\TodayTimespan;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MetricsCollectionSpec extends ObjectBehavior
{
    private $timespansCollection;
    private $todayTimespan;

    public function let(TimespansCollection $timespansCollection, TodayTimespan $todayTimespan)
    {
        $this->timespansCollection = $timespansCollection;
        $this->todayTimespan = $todayTimespan;
        $this->setTimespansCollection($timespansCollection);
        $this->setFiltersCollection(new FiltersCollection());
        $this->timespansCollection->setSelectedId('today');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MetricsCollection::class);
    }

    public function it_should_add_metrics_to_collection()
    {
        $this->addMetrics(
            new ActiveUsersMetric,
            new SignupsMetric,
            new ViewsMetric
        );
        $metrics = $this->getMetrics();
        $metrics['active_users']->getId()
            ->shouldBe('active_users');
    }

    public function it_should_export_metrics()
    {
        $this->addMetrics(
            new ActiveUsersMetric,
            new SignupsMetric,
            new ViewsMetric
        );
        $export = $this->export();
        $export[0]['id']
            ->shouldBe('active_users');
    }

    public function it_should_build_metrics(ActiveUsersMetric $activeUsersMetric)
    {
        $activeUsersMetric->getId()
            ->shouldBeCalled()
            ->willReturn('active_users');
        $activeUsersMetric->setTimespansCollection($this->timespansCollection)
            ->shouldBeCalled()
            ->willReturn($activeUsersMetric);
        $activeUsersMetric->setFiltersCollection(Argument::type(FiltersCollection::class))
            ->shouldBeCalled()
            ->willReturn($activeUsersMetric);
        $activeUsersMetric->buildSummary()
            ->shouldBeCalled();
        $this->addMetrics($activeUsersMetric);
        $this->buildSummaries();
    }
}
