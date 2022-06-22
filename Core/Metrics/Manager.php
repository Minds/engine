<?php
namespace Minds\Core\Metrics;

use Minds\Core\Entities\Actions;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Di\Di;
use Minds\Core\Data\Redis;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Email\SendGrid\Manager as SendGridManager;
use Minds\Core\Queue\SQS\Client as SQSClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp;
use Minds\Core\Blockchain\Services\Web3Services\MindsWeb3Service;
use Minds\Core\Permaweb\Manager as PermawebManager;

class Manager
{
    /** @var User */
    protected $user;

    /** @var Actions\Save */
    protected $save;

    public function __construct(
        private ?CassandraClient $cassandraClient = null,
        private ?Redis\Client $redisClient = null,
        private ?ElasticSearchClient $elasticSearchClient = null,
        private ?SendGridManager $sendGridManager = null,
        private ?SQSClient $sqsClient = null,
        private ?ClientInterface $httpClient = null,
        private ?MindsWeb3Service $web3Service = null,
        private ?PermawebManager $permaweb = null,
    ) {
        $this->cassandraClient = $this->cassandraClient ?? Di::_()->get('Database\Cassandra\Cql');
        $this->elasticSearchClient ??= Di::_()->get('Database\ElasticSearch');
        $this->sendGridManager ??= Di::_()->get('SendGrid\Manager');
        $this->sqsClient ??= Di::_()->get('Queue\SQS');
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
        $this->web3Service ??= Di::_()->get('Blockchain\Services\MindsWeb3');
        $this->permaweb ??= Di::_()->get('Permaweb\Manager');

        try {
            $this->redisClient = $this->redisClient ?? Di::_()->get("Redis");
        } catch (\Exception $e) {
        }
    }

    public function getMetrics()
    {
        $cassandraMetrics = $this->cassandraClient?->metrics();
        $redisInfo = $this->redisClient?->info();
        $esHealth = $this->elasticSearchClient?->health();
        $sendGrid = $this->sendGridManager?->checkAvailability();
        $web3ServiceHealthy = $this->web3Service?->checkHealth();
        $permawebAvailable = $this->permaweb?->checkHealth();
        $sqs = $this->isSQSAvailable();

        return [
            'cassandra' => $cassandraMetrics ?? null,
            'redis' => $redisInfo ?? null,
            'es' => $esHealth ?? null,
            'sendgrid' => $sendGrid ?? null,
            'web3' => $web3ServiceHealthy ?? null,
            'permaweb' => $permawebAvailable ?? null,
            'sqs' => $sqs ?? null,
        ];
    }

    // TODO
    private function isSQSAvailable()
    {
        try {
            $response = $this->httpClient->request('GET', $this->sqsClient->getQueueUrl());
            $responseData = json_decode($response->getBody()->getContents(), true);
            return $responseData['status'] && $responseData['status'] === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
