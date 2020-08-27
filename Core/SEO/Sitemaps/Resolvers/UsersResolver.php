<?php
/**
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Entities\User;
use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Minds\Core\SEO\Manager;
use Minds\Core\Security\ACL;

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
            $entity = $this->entitiesBuilder->single($raw['guid']);

            if (!$entity->username || !ACL::_()->read($entity)) {
                continue;
            }

            if ($entity->getSubscribersCount() < Manager::MIN_METRIC_FOR_ROBOTS) {
                continue;
            }

            if (!$entity->isEnabled()) {
                continue;
            }

            if (++$i > 20000) {
                break;
            }

            $lastModified = (new \DateTime)->setTimestamp($entity->last_login ?: $entity->time_created);
            $username = strtolower($entity->username);
            $sitemapUrl = new SitemapUrl();
            $sitemapUrl->setLoc("/$username")
                ->setChangeFreq('daily')
                ->setLastModified($lastModified);
            $this->logger->info("$i: @$entity->username");
            yield $sitemapUrl;
        }
    }
}
