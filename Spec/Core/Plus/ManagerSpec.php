<?php

namespace Spec\Minds\Core\Plus;

use Minds\Core\Config;
use Minds\Core\Data\Cassandra;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Plus\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;

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

        $this->config->get('plus')->willReturn([
            'support_tier_urn' => 'support_tier',
        ]);

        $this->es->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->willReturn([
                'hits' => [
                    'total' => 100,
                ],
                'aggregations' => [
                    'owners' => [
                        'buckets' => [
                            [
                                'key' => "123",
                                'doc_count' => 75,
                                'actions' => [
                                    'buckets' => [
                                        [
                                            'key' => 'vote:up',
                                            'unique_user_actions' => [
                                                'value' => 55,
                                            ]
                                        ],
                                        [
                                            'key' => 'vote:down',
                                            'unique_user_actions' => [
                                                'value' => 10,
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'key' => "456",
                                'doc_count' => 25,
                                'actions' => [
                                    'buckets' => [
                                        [
                                            'key' => 'vote:up',
                                            'unique_user_actions' => [
                                                'value' => 5,
                                            ]
                                        ],
                                        [
                                            'key' => 'vote_down',
                                            'unique_user_actions' => [
                                                'value' => 5,
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $response = $this->getScores($asOfTs);
        $current = $response->current();

        $current->shouldBe([
            'user_guid' => "123",
            'total' => 60,
            'count' => 55,
            'sharePct' => 0.9166666666666666
        ]);

        //
        $response->next();
        $response->current()->shouldBe([
            'user_guid' => "456",
            'total' => 60,
            'count' => 5,
            'sharePct' => 0.08333333333333333
        ]);
    }
}
