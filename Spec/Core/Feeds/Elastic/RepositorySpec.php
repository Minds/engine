<?php

namespace Spec\Minds\Core\Feeds\Elastic;

use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Feeds\Elastic\MetricsSync;
use Minds\Core\Feeds\Elastic\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $client;

    /** @var Config */
    protected $config;

    public function let(Client $client, Config $config)
    {
        $this->beConstructedWith($client, $config);

        $this->client = $client;
        $this->config = $config;

        $config->get('elasticsearch')
            ->shouldBeCalled()
            ->willReturn([
                'indexes' => [
                    'search_prefix' => 'minds-search'
                ]
            ]);

        $config->get('plus')
            ->shouldBeCalled()
            ->willReturn(['support_tier_urn' => 'urn:support-tier:fake/plus']);

        $config->get('tenant_id')
            ->willReturn(123);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_query_a_list_of_activity_guids()
    {
        $opts = [
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => '1y',
            'query' => 'test'
        ];

        $this->client->request(Argument::type(Search::class))
            ->shouldBeCalled()
            ->willReturn([
                'hits' => [
                    'hits' => [
                        [
                            '_source' => [
                                'guid' => '1',
                                'owner_guid' => '1000',
                                'time_created' => 1,
                                '@timestamp' => 1000,
                                'type' => 'activity',
                            ],
                            '_score' => 100,
                            '_index' => 'minds-search-activity',
                            '_type' => '_doc',
                        ],
                        [
                            '_source' => [
                                'guid' => '2',
                                'owner_guid' => '1000',
                                'time_created' => 1,
                                '@timestamp' => 1000,
                                'type' => 'activity',
                            ],
                            '_score' => 50,
                            '_index' => 'minds-search-activity',
                            '_type' => '_doc',
                        ],
                    ]
                ]
            ]);

        $gen = $this->getList($opts);

        $gen->current()->getGuid()->shouldReturn('1');
        $gen->current()->getScore()->shouldReturn(100.0);
        $gen->next();
        $gen->current()->getGuid()->shouldReturn('2');
        $gen->current()->getScore()->shouldReturn(50.0);
    }

    public function it_should_query_a_list_of_activity_guids_and_exclude_given_owner_guids()
    {
        $userGuid1 = '123456890';
        $userGuid2 = '123456891';

        $opts = [
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => '1y',
            'query' => 'test',
            'exclude_owner_guids' => [$userGuid1, $userGuid2]
        ];


        $this->client->request(Argument::that(function ($query) use ($userGuid1, $userGuid2) {
            foreach ($query->build()['body']['query']['function_score']['query']['bool']['must_not'] as $mustNot) {
                if (isset($mustNot['terms']) && isset($mustNot['terms']['owner_guid'])) {
                    foreach ($mustNot['terms']['owner_guid'] as $ownerGuid) {
                        if (in_array($ownerGuid, [$userGuid1, $userGuid2], true)) {
                            return true;
                        }
                    }
                    return false;
                }
            }
        }))
            ->shouldBeCalled()
            ->willReturn([
                'hits' => [
                    'hits' => [
                        [
                            '_source' => [
                                'guid' => '1',
                                'owner_guid' => '1000',
                                'time_created' => 1,
                                '@timestamp' => 1000,
                                'type' => 'activity',
                            ],
                            '_score' => 100,
                            '_index' => 'minds-search-activity',
                            '_type' => '_doc',
                        ],
                        [
                            '_source' => [
                                'guid' => '2',
                                'owner_guid' => '1000',
                                'time_created' => 1,
                                '@timestamp' => 1000,
                                'type' => 'activity',
                            ],
                            '_score' => 50,
                            '_index' => 'minds-search-activity',
                            '_type' => '_doc',
                        ],
                    ]
                ]
            ]);

        $gen = $this->getList($opts);

        $gen->current()->getGuid()->shouldReturn('1');
        $gen->current()->getScore()->shouldReturn(100.0);
        $gen->next();
        $gen->current()->getGuid()->shouldReturn('2');
        $gen->current()->getScore()->shouldReturn(50.0);
    }

    public function it_should_query_a_list_of_channel_guids()
    {
        $opts = [
            'type' => 'user',
            'algorithm' => 'top',
            'period' => '1y',
            'query' => 'test',
        ];

        $this->client->request(Argument::that(function ($query) {
            $query = $query->build();
            return $query['index'] === 'minds-search-user'
                && in_array('owner_guid', $query['body']['_source'], true);
        }))
            ->shouldBeCalled()
            ->willReturn([
                'hits' => [
                    'hits' => [
                        [
                            '_source' => [
                                'guid' => '1',
                                'owner_guid' => '1',
                                'time_created' => 1,
                                '@timestamp' => 1000,
                                'type' => 'user',
                            ],
                            '_score' => 100,
                            '_index' => 'minds-search-user',
                            '_type' => '_doc',
                        ],
                        [
                            '_source' => [
                                'guid' => '2',
                                'owner_guid' => '2',
                                'time_created' => 2,
                                '@timestamp' => 2000,
                                'type' => 'user',
                            ],
                            '_index' => 'minds-search-user',
                            '_score' => 50,
                            '_type' => '_doc',
                        ],
                    ]
                ]
            ]);

        $gen = $this->getList($opts);

        $gen->current()->getGuid()->shouldReturn('1');
        $gen->current()->getScore()->shouldReturn(log10(100.0));
        $gen->next();
        $gen->current()->getGuid()->shouldReturn('2');
        $gen->current()->getScore()->shouldReturn(log10(50.0));
    }

    public function it_should_query_a_list_of_group_guids()
    {
        $opts = [
            'type' => 'group',
            'algorithm' => 'top',
            'period' => '1y',
            'query' => 'test'
        ];

        $this->client->request(Argument::that(function ($query) {
            $query = $query->build();
            return $query['index'] === 'minds-search-group'
                && in_array('container_guid', $query['body']['_source'], true);
        }))
            ->shouldBeCalled()
            ->willReturn([
                'hits' => [
                    'hits' => [
                        [
                            '_source' => [
                                'guid' => '1',
                                'owner_guid' => '1000',
                                'time_created' => 1,
                                '@timestamp' => 1000,
                                'container_guid' => '1',
                                'type' => 'group',
                            ],
                            '_score' => 100,
                            '_type' => '_doc',
                            '_index' => 'minds-search-group',
                        ],
                        [
                            '_source' => [
                                'guid' => '2',
                                'owner_guid' => '1001',
                                'time_created' => 2,
                                '@timestamp' => 2000,
                                'container_guid' => '2',
                                'type' => 'group',
                            ],
                            '_score' => 50,
                            '_type' => '_doc',
                            '_index' => 'minds-search-group',
                        ],
                    ]
                ]
            ]);

        $gen = $this->getList($opts);

        $gen->current()->getGuid()->shouldReturn('1');
        $gen->current()->getScore()->shouldReturn(log10(100));
        $gen->next();
        $gen->current()->getGuid()->shouldReturn('2');
        $gen->current()->getScore()->shouldReturn(log10(50));
    }

    // Seems like yielded functions have issues with PHPSpec
    //
    // function it_should_throw_during_get_list_if_no_type()
    // {
    //     $this
    //         ->shouldThrow(new \Exception('Type must be provided'))
    //         ->duringGetList([
    //         'type' => '',
    //         'algorithm' => 'hot',
    //         'period' => '12h',
    //     ]);
    // }
    //
    // function it_should_throw_during_get_list_if_no_algorithm()
    // {
    //     $this
    //         ->shouldThrow(new \Exception('Algorithm must be provided'))
    //         ->duringGetList([
    //         'type' => 'activity',
    //         'algorithm' => '',
    //         'period' => '12h',
    //     ]);
    // }
    //
    // function it_should_throw_during_get_list_if_invalid_period()
    // {
    //     $this
    //         ->shouldThrow(new \Exception('Unsupported period'))
    //         ->duringGetList([
    //         'type' => 'activity',
    //         'algorithm' => 'hot',
    //         'period' => '!!',
    //     ]);
    // }
}
