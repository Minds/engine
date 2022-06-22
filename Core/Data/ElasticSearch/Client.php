<?php

/**
 * ElasticSearch Client
 *
 * @author emi
 */

namespace Minds\Core\Data\ElasticSearch;

use Elasticsearch;
use Minds\Core\Config\Config;
use Minds\Core\Data\Interfaces;
use Minds\Core\Di\Di;

class Client implements Interfaces\ClientInterface
{
    /** @var Elasticsearch\Client $elasticsearch */
    protected $elasticsearch;

    /**
     * Client constructor.
     */
    public function __construct(Config $config = null)
    {
        $config = $config ?? Di::_()->get('Config');
        $esConfig = $config->get('elasticsearch');
        $hosts = $esConfig['hosts'];
     
        $builder = Elasticsearch\ClientBuilder::create()
            ->setHosts($hosts)
            ->setBasicAuthentication($esConfig['username'] ?? '', $esConfig['password'] ?? '')
            ->setSSLVerification($esConfig['cert'] ?? false);

        // If cli, ping first
        if (php_sapi_name() === 'cli') {
            $builder->setConnectionPool('\Elasticsearch\ConnectionPool\StaticConnectionPool', []);
        }

        $this->elasticsearch = $builder->build();
    }

    /**
     * @param Interfaces\PreparedMethodInterface $query
     * @return mixed
     */
    public function request(Interfaces\PreparedMethodInterface $query)
    {
        return $this->elasticsearch->{$query->getMethod()}($query->build());
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function bulk($params = [])
    {
        return $this->elasticsearch->bulk($params);
    }

    /**
     * @return Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->elasticsearch;
    }

    public function health(): array
    {
        /*
        $params['local']          = (bool) Return local information, do not retrieve the state from master node (default: false)
               ['master_timeout'] = (time) Explicit operation timeout for connection to master node
               ['h']              = (list) Comma-separated list of column names to display
               ['help']           = (bool) Return help information
               ['v']              = (bool) Verbose mode. Display column headers
               ['ts']             = (bool) Set to false to disable timestamping
               ['body']  = (array) Request body
        */
        $params = [];
        return $this->elasticsearch->cat()->health($params)[0];
    }
}
