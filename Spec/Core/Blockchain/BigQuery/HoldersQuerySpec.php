<?php

namespace Spec\Minds\Core\Blockchain\BigQuery;

use Google\Cloud\BigQuery\QueryResults;
use Google\Cloud\Core\Iterator\ItemIterator;
use Minds\Core\Blockchain\BigQuery\HoldersQuery;
use Minds\Core\Config\Config;
use Minds\Core\Data\BigQuery\Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class HoldersQuerySpec extends ObjectBehavior
{
    /** @var Client */
    protected $client;
    
    /** @var Config */
    protected $config;

    public function let(
        Client $client,
        Config $config
    ) {
        $config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'token_address' => '0x00'
            ]);

        $this->beConstructedWith(
            $client,
            $config
        );

        $this->client = $client;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(HoldersQuery::class);
    }

    public function it_should_get(
        QueryResults $result,
    ) {
        $result->rows()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->client->query(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($result);

        $this->get();
    }
}
