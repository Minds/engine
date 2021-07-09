<?php

namespace Spec\Minds\Core\Feeds\Scheduled;

use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Count;
use Minds\Core\Feeds\Scheduled\Repository;
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
            ->willReturn(['indexes' => [
                'search_prefix' => 'minds'
            ]
            ]);

        $this->beConstructedWith($client, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_count_scheduled_activities()
    {
        $opts = ['container_guid' => 9999, 'type' => 'activity'];

        $this->client->request(Argument::type(Count::class))
            ->shouldBeCalled()
            ->willReturn([
                "count" => 1,
                "_shards" => [
                    "total" => 5,
                    "successful" => 5,
                    "skipped" => 0,
                    "failed" => 0
                ]
            ]);

        $this->getScheduledCount($opts);
    }
}
