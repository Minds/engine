<?php
/**
 * NOTE: this DOES NOT build dully hydrated entities. It uses partial data
 * readily returned via elasticsearch
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Scroll;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\SEO\Sitemaps\SitemapUrl;

abstract class AbstractEntitiesResolver
{
    /** @var Scroll */
    private $scroll;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Logger */
    protected $logger;

    /** @var string */
    protected $type;

    /** @var array */
    protected $query = [
        "bool" => [
            "must" => [
                [
                    "exists" => [
                        "field" => "guid",
                    ],
                ]
            ]
        ]
    ];

    /** @var array */
    protected $sort = [ '@timestamp' => 'desc' ];

    /** @var Config */
    protected $config;

    public function __construct($scroll = null, $entitiesBuilder = null, $logger = null, Config $config = null)
    {
        $this->scroll = $scroll ?? Di::_()->get('Database\ElasticSearch\Scroll');
        $this->entitiesBuilder = $entitiesBuilder ??  Di::_()->get('EntitiesBuilder');
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Returns raw data from the database as a scroll
     * @return iterable
     */
    public function getRawData(): iterable
    {
        $max = 100000;

        $prepared = new Search();
        $prepared->query([
            "index" => $this->config->get('elasticsearch')['indexes']['search_prefix'] . '-' . $this->type,
            "size" => 1000,
            "body" => [
                "query" => $this->query,
                'sort' => $this->sort,
            ],
        ]);
        $i = 0;
        foreach ($this->scroll->request($prepared) as $entity) {
            yield $entity['_source'];
            if (++$i > $max) {
                break;
            }
        }
    }

    /**
     * All resolvers must return urls
     * @return iterable
     */
    abstract public function getUrls(): iterable;
}
