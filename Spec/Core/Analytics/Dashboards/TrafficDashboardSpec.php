<?php

namespace Spec\Minds\Core\Analytics\Dashboards;

use Minds\Core\Analytics\Dashboards\TrafficDashboard;
use Minds\Core\Analytics\Dashboards\Timespans\TimespansCollection;
use Minds\Core\Analytics\Dashboards\Metrics\MetricsCollection;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Filters\FiltersCollection;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TrafficDashboardSpec extends ObjectBehavior
{
    private $timespansCollection;
    private $metricsCollection;
    private $filtersCollection;

    public function let(TimespansCollection $timespansCollection, MetricsCollection $metricsCollection, FiltersCollection $filtersCollection)
    {
        $this->beConstructedWith(null, $metricsCollection, null);
        $this->timespansCollection = $timespansCollection;
        $this->metricsCollection = $metricsCollection;
        $this->filtersCollection = $filtersCollection;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TrafficDashboard::class);
    }

    public function it_should_build_dashboard(AbstractMetric $mockMetric)
    {
        $user = new User();
        $this->setUser($user);
        $this->setTimespanId('today');
        $this->setFilterIds([
            'platform::browser'
        ]);

        // Metrics
        $this->metricsCollection->setTimespansCollection(Argument::type(TimespansCollection::class))
            ->willReturn($this->metricsCollection);

        $this->metricsCollection->setFiltersCollection(Argument::type(FiltersCollection::class))
            ->willReturn($this->metricsCollection);

        $this->metricsCollection->setSelectedId('active_users')
            ->willReturn($this->metricsCollection);

        $this->metricsCollection->setUser($user)
            ->willReturn($this->metricsCollection);

        $this->metricsCollection->addMetrics(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->metricsCollection);

        $this->metricsCollection->build()
            ->shouldBeCalled()
            ->willReturn($this->metricsCollection);

        $this->metricsCollection->getSelected()
            ->willReturn($mockMetric);

        $this->build();
    }
}
