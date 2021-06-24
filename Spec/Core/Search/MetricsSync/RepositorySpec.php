<?php

namespace Spec\Minds\Core\Search\MetricsSync;

use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Core\Search\MetricsSync\MetricsSync;
use Minds\Core\Search\MetricsSync\Repository;
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
        $this->client = $client;
        $this->config = $config;

        $config->get('elasticsearch')
            ->shouldBeCalled()
            ->willReturn([
                'indexes' => [
                    'search_prefix' => 'minds'
                ]
            ]);

        $this->beConstructedWith($client, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add(MetricsSync $metric)
    {
        $metric->getMetric()
            ->shouldBeCalled()
            ->willReturn('test');

        $metric->getPeriod()
            ->shouldBeCalled()
            ->willReturn('12h');

        $metric->getType()
            ->shouldBeCalled()
            ->willReturn('test');

        $metric->getCount()
            ->shouldBeCalled()
            ->willReturn(500);

        $metric->getSynced()
            ->shouldBeCalled()
            ->willReturn(100000);

        $metric->getGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $this->client->bulk(Argument::that(function ($arr) {
            return isset($arr['body']);
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->add($metric)
            ->shouldReturn(true);

        $this->bulk();
    }
}
