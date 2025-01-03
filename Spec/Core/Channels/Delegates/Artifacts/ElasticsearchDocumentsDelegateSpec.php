<?php

namespace Spec\Minds\Core\Channels\Delegates\Artifacts;

use OpenSearch\Client as ElasticsearchNativeClient;
use OpenSearch\Common\Exceptions\Missing404Exception;
use Minds\Core\Channels\Delegates\Artifacts\ElasticsearchDocumentsDelegate;
use Minds\Core\Channels\Snapshots\Repository;
use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch\Client as ElasticsearchClient;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ElasticsearchDocumentsDelegateSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Config */
    protected $config;

    /** @var ElasticsearchClient */
    protected $elasticsearch;

    /** @var ElasticsearchNativeClient */
    protected $esNativeClient;

    /** @var Logger */
    protected $logger;

    public function let(
        Repository $repository,
        Config $config,
        ElasticsearchClient $elasticsearch,
        ElasticsearchNativeClient $esNativeClient,
        Logger $logger
    ) {
        $this->beConstructedWith($repository, $config, $elasticsearch, $logger);

        $this->repository = $repository;
        $this->config = $config;
        $this->elasticsearch = $elasticsearch;

        $this->elasticsearch->getClient()
            ->willReturn($esNativeClient);

        $this->esNativeClient = $esNativeClient;
        $this->logger = $logger;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(ElasticsearchDocumentsDelegate::class);
    }

    public function it_should_snapshot()
    {
        $this
            ->snapshot(1000)
            ->shouldReturn(true);
    }

    public function it_should_restore()
    {
        $this->config->get('elasticsearch')
            ->shouldBeCalled()
            ->willReturn(['indexes' => [ 'search_prefix' => 'phpspec' ]]);

        $this->elasticsearch->getClient()
            ->shouldBeCalled()
            ->willReturn($this->esNativeClient);

        $this->esNativeClient->updateByQuery(Argument::that(function ($query) {
            return ($query['body']['query']['bool']['must'][0]['match']['owner_guid'] ?? null) === '1000';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->restore(1000)
            ->shouldReturn(true);
    }

    public function it_should_hide()
    {
        $this->config->get('elasticsearch')
            ->shouldBeCalled()
            ->willReturn(['indexes' => [ 'search_prefix' => 'phpspec' ]]);

        $this->elasticsearch->getClient()
            ->shouldBeCalled()
            ->willReturn($this->esNativeClient);

        $this->esNativeClient->updateByQuery(Argument::that(function ($query) {
            return ($query['body']['query']['bool']['must'][0]['match']['owner_guid'] ?? null) === '1000';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->hide(1000)
            ->shouldReturn(true);
    }

    public function it_should_delete()
    {
        $this->config->get('elasticsearch')
            ->shouldBeCalled()
            ->willReturn(['indexes' => [ 'search_prefix' => 'phpspec' ]]);

        $this->esNativeClient->delete([
            'index' => 'phpspec-user',
            'id' => 1000,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->esNativeClient->deleteByQuery(Argument::that(function ($query) {
            return ($query['body']['query']['bool']['must'][0]['match']['owner_guid'] ?? null) === '1000';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->delete(1000)
            ->shouldReturn(true);
    }

    public function it_should_delete_and_log_missing_exception()
    {
        $this->config->get('elasticsearch')
            ->shouldBeCalled()
            ->willReturn(['indexes' => [ 'search_prefix' => 'phpspec' ]]);

        $this->esNativeClient->delete([
            'index' => 'phpspec-user',
            'id' => 1000,
        ])
            ->shouldBeCalled()
            ->willThrow(new Missing404Exception('not-found'));

        $this->logger->info('not-found')
            ->shouldBeCalled();

        $this->esNativeClient->deleteByQuery(Argument::that(function ($query) {
            return ($query['body']['query']['bool']['must'][0]['match']['owner_guid'] ?? null) === '1000';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->delete(1000)
            ->shouldReturn(true);
    }
}
