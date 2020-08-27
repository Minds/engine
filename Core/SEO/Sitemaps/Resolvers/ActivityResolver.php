<?php
/**
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Entities\Activity;
use Minds\Core\Entities\Manager as EntitiesManager;
use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Minds\Core\SEO\Manager;
use Minds\Helpers;

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
                        "should" => [
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
                        ],
                        "minimum_should_match" => 1,
                    ]
                ];

    /** @var array */
    //protected $sort = [ 'votes:up' => 'desc' ];

    public function getUrls(): iterable
    {
        $i = 0;
        foreach ($this->getRawData() as $raw) {
            $entity = $this->entitiesBuilder->single($raw['guid']);

            if (!$entity || !\Minds\Core\Security\ACL::_()->read($entity)) {
                continue;
            }

            if ($entity->remind_object) {
                continue;
            }

            $thumbsUp = $entity->entity_guid ? Helpers\Counters::get($entity->entity_guid, 'thumbs:up') : Helpers\Counters::get($entity->guid, 'thumbs:up');
            if ($thumbsUp < Manager::MIN_METRIC_FOR_ROBOTS) {
                continue;
            }

            if (++$i > 100000) {
                break;
            }

            $lastModified = (new \DateTime)->setTimestamp($entity->time_created);
            $sitemapUrl = new SitemapUrl();
            $sitemapUrl->setLoc("/newsfeed/{$entity->guid}")
                ->setChangeFreq('never')
                ->setLastModified($lastModified);
            $this->logger->info("$i: {$entity->getUrl()}");
            yield $sitemapUrl;
        }
    }
}
