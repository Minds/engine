<?php
/**
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\Blogs\Legacy;
use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Minds\Core\SEO\Manager;

class BlogsResolver extends AbstractEntitiesResolver
{
    /** @var string */
    protected $type = 'object-blog';

    /** @var array */
    protected $query = [
                    "bool"=> [
                        "must" => [
                            [
                                "exists" => [
                                    "field" => "guid",
                                ],
                            ],
                            [
                                "range" => [
                                    "votes:up" => [
                                        'gte' => Manager::MIN_METRIC_FOR_ROBOTS,
                                    ]
                                ],
                            ],
                        ],
                        "must_not" => [
                            [
                                "term" => [
                                    "mature" => true,
                                ]
                            ],
                            [
                                "exists" => [
                                    "field" => "nsfw",
                                ]
                            ]
                        ],
                    ]
                ];

    /** @var array */
    protected $sort = [ 'votes:up' => 'desc' ];

    public function getUrls(): iterable
    {
        $i = 0;
        foreach ($this->getRawData() as $raw) {
            $entity = $this->entitiesBuilder->single($raw['guid']);
            if (!$entity) {
                continue;
            }
            ++$i;
            $lastModified = (new \DateTime)->setTimestamp($entity->time_created);
            $sitemapUrl = new SitemapUrl();
            $sitemapUrl->setLoc("/" . $entity->getUrl(true))
                ->setChangeFreq('never')
                ->setPriority(0.5)
                ->setLastModified($lastModified);
            error_log("$i: {$entity->getUrl()}");
            yield $sitemapUrl;
        }
    }
}
