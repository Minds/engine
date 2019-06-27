<?php

/**
 * ElasticSearch Client
 *
 * @author emi
 */

namespace Minds\Core\Data\ElasticSearch;

use Elasticsearch;

use Minds\Core\Data\Interfaces;
use Minds\Core\Di\Di;
use Minds\Traits\Logger;

class Client implements Interfaces\ClientInterface
{
    use Logger;

    /** @var Elasticsearch\Client $elasticsearch */
    protected $elasticsearch;

    /** @var bool */
    protected $debug;

    /**
     * Client constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $hosts = Di::_()->get('Config')->elasticsearch['hosts'];

        $this->elasticsearch = Elasticsearch\ClientBuilder::create()
            ->setHosts($hosts)
            ->build();

        $this->debug = (bool) Di::_()->get('Config')->get('minds_debug');
    }

    /**
     * @param Interfaces\PreparedMethodInterface $query
     * @return mixed
     */
    public function request(Interfaces\PreparedMethodInterface $query)
    {
        if ($this->debug) {
            $this->logger()->debug("{$query->getMethod()}: " . json_encode($query->build()));
        }

        return $this->elasticsearch->{$query->getMethod()}($query->build());
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function bulk($params = [])
    {
        if ($this->debug) {
            $this->logger()->debug(json_encode($params));
        }

        return $this->elasticsearch->bulk($params);
    }

    /**
     * @return Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->elasticsearch;
    }
}
