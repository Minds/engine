<?php
/**
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Entities\User;
use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Minds\Core\SEO\Manager;

class UsersResolver extends AbstractEntitiesResolver
{
    /** @var string */
    protected $type = 'user';

    /** @var array */
    protected $query = [
        'bool' => [
            'must' => [
                [
                    'exists' => [
                        'field' => 'guid',
                    ],
                ],
                [
                    'exists' => [
                        'field' => 'username',
                    ],
                ],
                [
                    "regexp" => [
                        "briefdescription" => ".+"
                    ],
                ],
            ],
            'must_not' => [
                [
                    'term' => [
                        'deleted' => true,
                    ]
                ]
            ]
        ]
    ];

    /** @var array */
    protected $sort = [
        '@timestamp' => 'asc'
    ];

    public function getUrls(): iterable
    {
        $i = 0;
        foreach ($this->getRawData() as $raw) {
            $entity = new User($raw);

            if (!$entity->username) {
                continue;
            }

            if ($entity->getSubscribersCount() < Manager::MIN_METRIC_FOR_ROBOTS) {
                continue;
            }

            ++$i;
            $lastModified = (new \DateTime)->setTimestamp($entity->last_login ?: $entity->time_created);
            $sitemapUrl = new SitemapUrl();
            $sitemapUrl->setLoc("/$entity->username")
                ->setChangeFreq('daily')
                ->setLastModified($lastModified);
            $this->logger->info("$i: @$entity->username");
            yield $sitemapUrl;
        }
    }
}
