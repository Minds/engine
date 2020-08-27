<?php

namespace Spec\Minds\Core\Plus;

use Minds\Core\Plus\Manager;
use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Data\Cassandra;
use Spec\Minds\Mocks\Cassandra\Rows;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var ElasticSearch\Client */
    protected $es;

    /** @var Cassandra\Client */
    protected $db;

    public function let(
        Config $config,
        ElasticSearch\Client $es,
        Cassandra\Client $db
    ) {
        $this->config = $config;
        $this->es = $es;
        $this->db = $db;
        $this->beConstructedWith($config, $es, $db);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_plus_guid()
    {
        $this->config->get('plus')
            ->willReturn([
                'handler' => "123",
            ]);

        $this->getPlusGuid()
            ->shouldBe("123");
    }

    public function it_should_return_monthly_subscription_price()
    {
        $this->config->get('upgrades')
            ->willReturn([
                'plus' => [
                    'monthly' => [
                        'usd' => 7
                    ]
                ]
            ]);

        $this->getSubscriptionPrice("month")
            ->shouldBe(700);
    }

    public function it_should_return_yearly_subscription_price()
    {
        $this->config->get('upgrades')
            ->willReturn([
                'plus' => [
                    'yearly' => [
                        'usd' => 60
                    ]
                ]
            ]);

        $this->getSubscriptionPrice("year")
            ->shouldBe(6000);
    }

    public function it_should_return_active_amortized_revenue()
    {
        $asOfTs = strtotime('midnight 1st June 2020');
        $this->config->get('plus')->willReturn([
            'handler' => "123",
        ]);

        $this->config->get('upgrades')->willReturn([
            'plus' => [
                'monthly' => [
                    'usd' => 7,
                ],
                'yearly' => [
                    'usd' => 60,
                ]
            ]
        ]);

        // Monthly query
        $this->db->request(Argument::that(function ($prepared) use ($asOfTs) {
            $values = $prepared->build()['values'];

            $fromTs = $values[1]->time();
            $toTs = $values[2]->time();

            return $fromTs === strtotime('midnight 2nd May 2020')
                && $toTs === strtotime('midnight 1st June 2020');
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([
                [
                    'wei_sum' => 1000
                ]
            ], ''));

        // Yearly query
        $this->db->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
    
            $fromTs = $values[1]->time();
            $toTs = $values[2]->time();
    
            return $fromTs === strtotime('midnight 2nd June 2019')
                && $toTs === strtotime('midnight 1st June 2020');
        }))
                ->shouldBeCalled()
                ->willReturn(new Rows([
                    [
                        'wei_sum' => 1000
                    ]
                ], ''));
        
        $this->getActiveRevenue($asOfTs)
            ->shouldBe(round(10 + (10 / 12), 2));
    }

    public function it_should_get_unlocks()
    {
        $asOfTs = strtotime('midnight 1st June 2020');

        $this->es->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->willReturn([
                'hits' => [
                    'total' => 100,
                ],
                'aggregations' => [
                    'unlocks_by_owner' => [
                        'buckets' => [
                            [
                                'key' => "123",
                                'doc_count' => 75,
                            ],
                            [
                                'key' => "456",
                                'doc_count' => 25,
                            ]
                        ]
                    ]
                ]
            ]);

        $response = $this->getUnlocks($asOfTs);
        $response->current()->shouldBe([
            'user_guid' => "123",
            'total' => 100,
            'count' => 75,
            'sharePct' => 0.75
        ]);

        //
        $response->next();
        $response->current()->shouldBe([
            'user_guid' => "456",
            'total' => 100,
            'count' => 25,
            'sharePct' => 0.25
        ]);
    }
}
