<?php

namespace Spec\Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Analytics\Dashboards\Metrics\ViewsMetric;
use Minds\Core\Analytics\Dashboards\Metrics\ActiveUsersMetric;
use Minds\Core\Analytics\Dashboards\Timespans\TimespansCollection;
use Minds\Core\Analytics\Dashboards\Timespans\AbstractTimespan;
use Minds\Core\Analytics\Dashboards\Filters\FiltersCollection;
use Minds\Core\Analytics\Dashboards\Filters\AbstractFilter;
use Minds\Core\Data\Elasticsearch\Client;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ViewsMetricSpec extends ObjectBehavior
{
    private $es;
    private $timespansCollection;
    private $filtersCollection;

    public function let(Client $es, TimespansCollection $timespansCollection, FiltersCollection $filtersCollection)
    {
        $this->beConstructedWith($es);
        $this->es = $es;
        $this->setTimespansCollection($timespansCollection);
        $this->setFiltersCollection($filtersCollection);
        $this->timespansCollection = $timespansCollection;
        $this->filtersCollection = $filtersCollection;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ViewsMetric::class);
    }

    public function it_should_build_summary(AbstractTimespan $mockTimespan, AbstractFilter $mockFilter)
    {
        $this->setUser(new User());
        $this->timespansCollection->getSelected()
            ->willReturn($mockTimespan);
        $this->filtersCollection->getSelected()
            ->willReturn([$mockFilter]);

        $this->es->request(Argument::any())
            ->willReturn(
                [
                    'aggregations' => [
                        '1' => [
                            'value' => 128,
                        ]
                    ]
                ],
                [
                    'aggregations' => [
                        '1' => [
                            'value' => 256,
                        ]
                    ]
                ]
            );

        $this->buildSummary();

        $this->getSummary()->getValue()
            ->shouldBe(128);
        $this->getSummary()->getComparisonValue()
            ->shouldBe(256);
    }

    public function it_should_build_visualisation(AbstractTimespan $mockTimespan, AbstractFilter $mockFilter)
    {
        $this->setUser(new User());
        $this->timespansCollection->getSelected()
            ->willReturn($mockTimespan);
        $this->filtersCollection->getSelected()
            ->willReturn([$mockFilter]);

        $this->es->request(Argument::any())
            ->willReturn([
                'aggregations' => [
                    '1' => [
                        'buckets' => [
                            [
                                'key' => strtotime('Midnight 1st December 2018') * 1000,
                                '2' => [
                                    'value' => 256,
                                ],
                            ],
                            [
                                'key' => strtotime('Midnight 1st January 2019') * 1000,
                                '2' => [
                                    'value' => 128,
                                ],
                            ],
                            [
                                'key' => strtotime('Midnight 1st February 2019') * 1000,
                                '2' => [
                                    'value' => 4,
                                ],
                            ],
                            [
                                'key' => strtotime('Midnight 1st March 2019') * 1000,
                                '2' => [
                                    'value' => 685,
                                ],
                            ],
                        ],
                    ],
                ]
            ]);

        $this->buildVisualisation();

        $buckets = $this->getVisualisation()->getBuckets();
        // $xValues[0]->shouldBe('01-12-2018');
        // $xValues[1]->shouldBe('01-01-2019');
        // $xValues[2]->shouldBe('01-02-2019');
        // $xValues[3]->shouldBe('01-03-2019');
    }
}
