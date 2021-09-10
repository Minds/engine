<?php
/**
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Entities\Activity;
use Minds\Core\Entities\Manager as EntitiesManager;
use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Minds\Core\SEO\Manager;

class ActivityResolver extends AbstractEntitiesResolver
{
    /** @var string */
    protected $type = 'activity';

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
                        /*"should" => [
                            [
                                "regexp" => [
                                  "message" => ".+"
                                ],
                            ],
                            [
                                "regexp" => [
                                  "title" => ".+"
                                ],
                            ]
                        ],*/
                        //"minimum_should_match" => 1,
                    ]
                ];

    /** @var array */
    protected $sort = [ 'votes:up' => 'desc' ];

    public function getUrls(): iterable
    {
        $i = 0;
        foreach ($this->getRawData() as $raw) {
            $entity = new Activity($raw);

            if (!($entity->getMessage() || $entity->getTitle())) {
                continue;
            }

            ++$i;
            $lastModified = (new \DateTime)->setTimestamp($entity->time_created);
            $sitemapUrl = new SitemapUrl();
            $sitemapUrl->setLoc("/newsfeed/{$entity->guid}")
                ->setChangeFreq('never')
                ->setPriority(0.1)
                ->setLastModified($lastModified);
            $this->logger->info("$i: {$entity->getUrl()}");
            yield $sitemapUrl;
        }
    }
}
