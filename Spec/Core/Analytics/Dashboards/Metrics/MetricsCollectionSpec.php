<?php

namespace Spec\Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Analytics\Dashboards\Metrics\MetricsCollection;
use Minds\Core\Analytics\Dashboards\Metrics\ActiveUsersMetric;
use Minds\Core\Analytics\Dashboards\Metrics\SignupsMetric;
use Minds\Core\Analytics\Dashboards\Metrics\ViewsMetric;
use Minds\Core\Analytics\Dashboards\Timespans\TimespansCollection;
use Minds\Core\Analytics\Dashboards\Filters\FiltersCollection;
use Minds\Core\Analytics\Dashboards\Timespans\TodayTimespan;
use Minds\Entities\User;
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
        $this->setUser(new User());
        $this->addMetrics(
            // new ActiveUsersMetric,
            // new SignupsMetric,
            new ViewsMetric
        );
        $metrics = $this->getMetrics();
        $metrics['views']->getId()
            ->shouldBe('views');
    }

    public function it_should_export_metrics()
    {
        $this->setUser(new User());
        $this->addMetrics(
            new ActiveUsersMetric, // Not admin so won't export
            new SignupsMetric, // Not admin so wont export
            new ViewsMetric
        );
        $export = $this->export();
        $export[0]['id']
            ->shouldBe('views');
    }

    public function it_should_build_metrics(ActiveUsersMetric $activeUsersMetric)
    {
        $this->setUser(new User());
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
        $activeUsersMetric->getPermissions()
            ->willReturn([ 'user ']);
        $activeUsersMetric->setUser(Argument::any())
            ->wilLReturn($activeUsersMetric);
        $this->addMetrics($activeUsersMetric);
        $this->buildSummaries();
    }
}
