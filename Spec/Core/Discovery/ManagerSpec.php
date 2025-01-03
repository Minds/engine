<?php

namespace Spec\Minds\Core\Discovery;

use Minds\Core\Session;
use Minds\Core;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Hashtags\User\Manager as HashtagManager;
use Minds\Core\Feeds\Elastic\Manager as ElasticFeedsManager;
use Minds\Core\Hashtags\Trending\Manager as TrendingHashtagManager;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Common\Repository\Response;
use Minds\Core\Discovery\Manager;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var ElasticSearch\Client */
    protected $es;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var HashtagManager */
    private $hashtagManager;

    /** @var ElasticFeedsManager */
    private $elasticFeedsManager;

    /** @var User */
    private $user;

    private Collaborator $aclMock;

    private Collaborator $trendingHashtagManagerMock;

    public function let(ElasticSearch\Client $es, EntitiesBuilder $entitiesBuilder, HashtagManager $hashtagManager, ElasticFeedsManager $elasticFeedsManager, User $user, ACL $aclMock, TrendingHashtagManager $trendingHashtagManagerMock)
    {
        $this->beConstructedWith($es, $entitiesBuilder, null, $hashtagManager, $elasticFeedsManager, $user, $aclMock, $trendingHashtagManagerMock);
        $this->es = $es;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->hashtagManager = $hashtagManager;
        $this->hashtagManager
            ->setUser(Argument::any())
            ->willReturn($this->hashtagManager);
        $this->elasticFeedsManager = $elasticFeedsManager;
        $this->user = $user;
        $this->aclMock = $aclMock;
        $this->trendingHashtagManagerMock = $trendingHashtagManagerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_combined_tag_trends()
    {
        $this->hashtagManager
            ->get(Argument::any())
            ->willReturn([
                [ 'value' => 'music', ],
                [ 'value' => 'beatles', ],
            ]);
        $this->es->request(Argument::any())
            ->willReturn(
                [
                'aggregations' => [
                    'tags' => [
                        'buckets' => [
                            [
                                'key' => 'music',
                                'doc_count' => 100
                            ],
                            [
                                'key' => 'beatles',
                                'doc_count' => 50
                            ]
                        ]
                    ]
                ]
            ],
                // 2nd call will return below
                [
                    'aggregations' => [
                        'tags' => [
                            'buckets' => [
                                [
                                    'key' => 'technology',
                                    'doc_count' => 100
                                ],

                            ]
                        ]
                    ]
                ],
                // 3rd
                [
                    'aggregations' => [
                        'tags' => [
                            'buckets' => [
                                [
                                    'key' => 'animation',
                                    'doc_count' => 100
                                ],
                                [
                                    'key' => 'phpspec',
                                    'doc_count' => 100
                                ],
                            ]
                        ]
                    ]
                ]
            );

        $tagTrends = $this->getTagTrends();
        $tagTrends->shouldHaveCount(5);
        $tagTrends[0]
            ->getHashtag()
            ->shouldBe('music');
        $tagTrends[0]
            ->getVolume()
            ->shouldBe(100);
        $tagTrends[1]
            ->getHashtag()
            ->shouldBe('beatles');
        $tagTrends[1]
            ->getVolume()
            ->shouldBe(50);
        $tagTrends[2]
            ->getHashtag()
            ->shouldBe('technology');
        $tagTrends[2]
            ->getVolume()
            ->shouldBe(100);
    }

    public function it_should_return_post_trends(Activity $activityMock)
    {
        $this->es->request(Argument::any())
            ->willReturn([
                'hits' => [
                    'hits' => [
                        [
                            '_id' => 123,
                            '_source' => [
                                'title' => 'hello world',
                                'tags' => [ 'music', 'beatles' ],
                                'comments:count' => 52,
                                'owner_guid' => 1234,
                            ]
                        ],
                        [
                            '_id' => 456,
                            '_source' => [
                                'title' => 'goodbye world',
                                'tags' => [ 'music', 'pinkfloyd' ],
                                'comments:count' => 12,
                                'owner_guid' => 5678,
                            ]
                        ]
                    ]
                ]
            ]);

        $activityMock->getType()->willReturn('activity');
        $activityMock->getGuid()->willReturn(123);
        $activityMock->getOwnerGuid()->willReturn(1);
        $activityMock->get("owner_guid")->willReturn(1);
        $activityMock->getSpam()->willReturn(false);
        $activityMock->getDeleted()->willReturn(false);
        $activityMock->get("access_id")->willReturn("2");
        $activityMock->get(Argument::any())->willReturn("");
        $activityMock->getTimeCreated()->willReturn(time());
        $activityMock->export()->willReturn([
            'thumbnail_src' => 'test',
        ]);

        $this->entitiesBuilder
            ->single(123)
            ->willReturn(
                $activityMock
                // (new Activity())
                //     ->set('guid', '123')
                //     ->setThumbnail('test')
            );

        $this->entitiesBuilder
            ->single(456)
            ->willReturn(
                $activityMock
            );

        $this->aclMock->read(Argument::any())->willReturn(true);

        $postTrends = $this->getPostTrends([ 'music' ], [ 'shuffle' => false ]);
        $postTrends[0]
            ->getId('123');
        $postTrends[0]
            ->getTitle('hello world');
        $postTrends[1]
            ->getId('456');
        $postTrends[1]
            ->getTitle('goodbye world');
    }

    public function it_should_return_search()
    {
        $this->elasticFeedsManager
            ->getList(Argument::any())
            ->willReturn(new Response([
                (new FeedSyncEntity()),
                (new FeedSyncEntity())
            ]));

        $entities = $this->getSearch('hello world', 'top');
        $entities->shouldHaveCount(2);
    }

    public function it_should_return_tags()
    {
        $this->hashtagManager
            ->get([
                'defaults' => true,
                'trending' => true,
                'limit' => 20,
                'wire_support_tier' => null
            ])
            ->willReturn([
                [
                    'type' => 'user',
                    'tag' => 'music',
                ],
                [
                    'type' => 'trending',
                    'tag' => 'beatles',
                ]
            ]);

        $this->hashtagManager
            ->get([
                'defaults' => true,
                'limit' => 24
            ])
            ->willReturn([]);

        $tags = $this->getTags();
        $tags['tags']->shouldHaveCount(1);
        $tags['tags'][0]->shouldBe([
            'type' => 'user',
            'tag' => 'music',
        ]);
        $tags['trending']->shouldHaveCount(1);
        $tags['trending'][0]->shouldBe([
            'type' => 'trending',
            'tag' => 'beatles',
        ]);
    }

    public function it_shoud_return_search_count()
    {
        $this->elasticFeedsManager
            ->getCount(Argument::any())
            ->willReturn(5);

        $entities = $this->getSearchCount('test search count', 'latest');
        $entities->shouldBe(5);
    }
}
