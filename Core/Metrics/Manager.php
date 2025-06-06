<?php
namespace Minds\Core\Metrics;

use Minds\Core\Entities\Actions;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Di\Di;
use Minds\Core\Data\Redis;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Email\SendGrid\Manager as SendGridManager;
use GuzzleHttp\ClientInterface;
use GuzzleHttp;
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
        private ?ClientInterface $httpClient = null,
        private ?PermawebManager $permaweb = null,
    ) {
        $this->cassandraClient = $this->cassandraClient ?? Di::_()->get('Database\Cassandra\Cql');
        $this->elasticSearchClient ??= Di::_()->get('Database\ElasticSearch');
        $this->sendGridManager ??= Di::_()->get('SendGrid\Manager');
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
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
        $permawebAvailable = $this->permaweb?->checkHealth();

        return [
            'cassandra' => $cassandraMetrics ?? null,
            'redis' => $redisInfo ?? null,
            'es' => $esHealth ?? null,
            'sendgrid' => $sendGrid ?? null,
            'permaweb' => $permawebAvailable ?? null,
        ];
    }

}
