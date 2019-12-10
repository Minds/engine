<?php

namespace Spec\Minds\Core\Analytics\EntityCentric;

use Minds\Core\Analytics\EntityCentric\BoostViewsDaily;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BoostViewsDailySpec extends ObjectBehavior
{
    /** @var Client */
    protected $esClient;
    /** @var array */
    protected $response;

    public function let(Client $esClient)
    {
        $this->beConstructedWith($esClient);
        $this->esClient = $esClient;
        $this->response = [
            'aggregations' => [
                'boost_views_total' => [
                    'value' => 1887
                ],
                'boost_views_daily' => [
                    'buckets' => [
                        ['key' => '1570060800', 'boost_views' => ['value' => 242]],
                        ['key' => '1570147200', 'boost_views' => ['value' => 256]],
                        ['key' => '1570233600', 'boost_views' => ['value' => 287]],
                        ['key' => '1570320000', 'boost_views' => ['value' => 267]],
                        ['key' => '1570406400', 'boost_views' => ['value' => 249]],
                        ['key' => '1570492800', 'boost_views' => ['value' => 290]],
                        ['key' => '1570579200', 'boost_views' => ['value' => 296]]
                    ]
                ]
            ]
        ];
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostViewsDaily::class);
    }

    public function it_should_set_last_seven_days_range()
    {
        $this->lastSevenDays()->shouldReturn($this);
    }

    public function it_should_set_date_range()
    {
        $start = strtotime('yesterday -1 day');
        $end = strtotime('yesterday');

        $this->dateRange($start, $end)->shouldReturn($this);
    }

    public function it_should_return_array_of_daily_views()
    {
        $this->esClient->request(Argument::type(Search::class))->shouldBeCalled()->willReturn($this->response);
        $this->getAll()->shouldReturn([
            '1570060800' => 242,
            '1570147200' => 256,
            '1570233600' => 287,
            '1570320000' => 267,
            '1570406400' => 249,
            '1570492800' => 290,
            '1570579200' => 296
        ]);
    }

    public function it_should_return_total_views()
    {
        $this->esClient->request(Argument::type(Search::class))->shouldBeCalled()->willReturn($this->response);
        $this->getTotal()->shouldReturn(1887);
    }

    public function it_should_return_max_views()
    {
        $this->esClient->request(Argument::type(Search::class))->shouldBeCalled()->willReturn($this->response);
        $this->getMax()->shouldReturn(296);
    }

    public function it_should_return_avg_views()
    {
        $this->esClient->request(Argument::type(Search::class))->shouldBeCalled()->willReturn($this->response);
        $this->getAvg()->shouldBeApproximately(269.57, 0.01);
    }
}
