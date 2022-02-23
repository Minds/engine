<?php

namespace Minds\Core\Recommendations\Algorithms\FriendsOfFriends;

use Minds\Core\Config\Config;
use Elasticsearch\Client as ElasticSearchClient;
use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Recommendations\RepositoryInterface;
use NotImplementedException;

/**
 * Responsible for fetching the relevant entities from ElasticSearch for the FriendsOfFriends algorithm
 */
class Repository implements RepositoryInterface
{
    public function __construct(
        private ?ElasticSearchClient $elasticSearchClient = null,
        private ?RepositoryOptions $options = null,
        private ?Config $config = null
    ) {
        $this->elasticSearchClient ??= Di::_()->get('Database\ElasticSearch');
        $this->options ??= new RepositoryOptions();
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Returns a list of entities
     * @param array|null $options
     * @return Response
     * @throws NotImplementedException
     */
    public function getList(?array $options = null): Response
    {
        $this->options->init($options);
        if ($this->options->validate()) {
            throw new NotImplementedException();
        }
    }
}
