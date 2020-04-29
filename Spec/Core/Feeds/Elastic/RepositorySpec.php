<?php

namespace Spec\Minds\Core\Feeds\Elastic;

use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Features\Manager as FeaturesManager;
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

    /** @var FeaturesManager */
    protected $features;

    public function let(Client $client, Config $config, FeaturesManager $features)
    {
        $this->client = $client;
        $this->config = $config;
        $this->features = $features;

        $config->get('elasticsearch')
            ->shouldBeCalled()
            ->willReturn(['index' => 'minds']);

        $this->beConstructedWith($client, $config, $features);
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
                            ],
                            '_score' => 100,
                            '_type' => 'activity',
                        ],
                        [
                            '_source' => [
                                'guid' => '2',
                                'owner_guid' => '1000',
                                'time_created' => 1,
                                '@timestamp' => 1000,
                            ],
                            '_score' => 50,
                            '_type' => 'activity',
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
            return $query['type'] === 'activity,object:image,object:video,object:blog' && in_array('owner_guid', $query['body']['_source'], true);
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
                            ],
                            '_score' => 100,
                            '_type' => 'user',
                        ],
                        [
                            '_source' => [
                                'guid' => '2',
                                'owner_guid' => '2',
                                'time_created' => 2,
                                '@timestamp' => 2000,
                            ],
                            '_score' => 50,
                            '_type' => 'user',
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
            return $query['type'] === 'activity,object:image,object:video,object:blog' && in_array('container_guid', $query['body']['_source'], true);
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
                            ],
                            '_score' => 100,
                            '_type' => 'group',
                        ],
                        [
                            '_source' => [
                                'guid' => '2',
                                'owner_guid' => '1001',
                                'time_created' => 2,
                                '@timestamp' => 2000,
                                'container_guid' => '2',
                            ],
                            '_score' => 50,
                            '_type' => 'group',
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
