<?php

namespace Spec\Minds\Core\Data\BigQuery;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\JobConfigurationInterface;
use Google\Cloud\BigQuery\QueryResults;
use Minds\Core\Config\Config;
use Minds\Core\Data\BigQuery\Client;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;

class ClientSpec extends ObjectBehavior
{
    /** @var BigQueryClient */
    protected $client;
    
    /** @var Config */
    protected $config;

    /** @var Logger */
    protected $logger;

    public function let(
        BigQueryClient $client,
        Config $config,
        Logger $logger
    ) {
        $this->beConstructedWith(
            $client,
            $config,
            $logger
        );

        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_should_query(
        JobConfigurationInterface $jobConfig,
        QueryResults $queryResults
    ) {
        $query = 'SELECT * FROM [publicdata:samples.shakespeare] LIMIT 10';

        $this->client->query($query)
            ->shouldBeCalled()
            ->willReturn($jobConfig);

        $this->client->runQuery($jobConfig)
            ->shouldBeCalled()
            ->willReturn($queryResults);

        $queryResults->isComplete()
            ->willReturn(true);

        $this->query($query);
    }


    public function it_should_throw_exception_on_incomplete_query(
        JobConfigurationInterface $jobConfig,
        QueryResults $queryResults
    ) {
        $query = 'SELECT * FROM [publicdata:samples.shakespeare] LIMIT 10';

        $this->client->query($query)
            ->shouldBeCalled()
            ->willReturn($jobConfig);

        $this->client->runQuery($jobConfig)
            ->shouldBeCalled()
            ->willReturn($queryResults);

        $queryResults->isComplete()
            ->willReturn(false);

        $this->shouldThrow(ServerErrorException::class)
            ->duringQuery($query);
    }
}
