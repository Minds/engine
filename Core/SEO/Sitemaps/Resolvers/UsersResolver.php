<?php
/**
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Entities\User;
use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Minds\Core\SEO\Manager;
use Minds\Core\Blockchain\Wallets\Balance;

class UsersResolver extends AbstractEntitiesResolver
{
    /** @var Balance */
    protected $balance;

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

    public function __construct($balance = null)
    {
        parent::__construct();
        $this->balance = $balance ?: new Balance();
    }

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

            if ($entity->getDeleted()) {
                continue;
            }

            // Don't map users without token balances bc their pages
            // require login (as spam reduction measure)
            if ($this->balance->setUser($entity)->count() === 0) {
                continue;
            }

            ++$i;
            $lastModified = (new \DateTime)->setTimestamp($entity->last_login ?: $entity->time_created);
            $sitemapUrl = new SitemapUrl();
            $sitemapUrl->setLoc("/$entity->username")
                ->setChangeFreq('daily')
                ->setPriority(0.7)
                ->setLastModified($lastModified);
            $this->logger->info("$i: @$entity->username");
            yield $sitemapUrl;
        }
    }
}
