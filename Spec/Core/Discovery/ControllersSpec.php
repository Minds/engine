<?php

namespace Spec\Minds\Core\Discovery;

use Minds\Core\Discovery\Controllers;
use Minds\Core\Discovery\Trend;
use Minds\Core\Discovery\Manager;
use Minds\Common\Repository\Response;
use Minds\Entities\Activity;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ControllersSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    public function let(Manager $manager)
    {
        $this->beConstructedWith($manager);
        $this->manager = $manager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controllers::class);
    }

    // public function it_should_get_trends_response(ServerRequest $request)
    // {
    //     $request->getQueryParams()
    //         ->willReturn(['shuffle' => false]);

    //     $this->manager->getTagTrends(Argument::any())
    //         ->willReturn([
    //             (new Trend)
    //                 ->setHashtag('music'),
    //             (new Trend)
    //                 ->setHashtag('beatles'),
    //         ]);
    //     $this->manager->getPostTrends([ 'music', 'beatles' ], Argument::any())
    //         ->willReturn([
    //             (new Trend)
    //                 ->setGuid('123'),
    //         ]);

    //     $response = $this->getTrends($request);
    //     $json = $response->getBody()->getContents();

    //     $json->shouldBe(json_encode([
    //         'status' => 'success',
    //         'trends' => [
    //             (new Trend)
    //                 ->setHashtag('music')
    //                 ->export(),
    //             (new Trend)
    //                 ->setHashtag('beatles')
    //                 ->export()
    //         ],
    //         'hero' => (new Trend)
    //                     ->setGuid('123')
    //                     ->export()
    //     ]));
    // }

    public function it_should_get_search_response(
        ServerRequest $request
    ) {
        $request->getQueryParams()
            ->willReturn([
                'q' => 'hello world',
                'algorithm' => 'top',
                'type' => ''
            ]);

        $response = new Response([
            (new Activity())
                ->set('guid', '123'),
            (new Activity())
                ->set('guid', '456'),
        ]);

        $this->manager->getSearch('hello world', 'top', '', [ 'plus' => false, 'nsfw' => false ])
            ->willReturn($response);

        $response = $this->getSearch($request);
        $json = $response->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'entities' => [
                (new Activity())
                    ->set('guid', '123')
                    ->export(),
                (new Activity())
                    ->set('guid', '456')
                    ->export(),
            ]
        ]));
    }



    public function it_should_get_search_count_response(
        ServerRequest $request
    ) {
        $request->getQueryParams()
            ->willReturn([
                'q' => 'hello world',
                'algorithm' => 'latest',
                'from_timestamp' => '1651152940243',
            ]);

        $this->manager->getSearchCount(Argument::any(), Argument::any(), Argument::any(), Argument::any())->willReturn(5);

        $response = $this->getSearchCount($request);
        $json = $response->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'count' => 5
        ]));
    }

    public function it_should_get_tags_response(ServerRequest $request)
    {
        $opts = [
            'wire_support_tier' => null,
            'trending_tags_v2' => false,
        ];

        $this->manager->getTags($opts)
            ->willReturn([
                'tags' => [
                    [
                        'selected' => true,
                        'value' => 'music',
                        'type' => 'user',
                    ],
                    [
                        'selected' => true,
                        'value' => 'beatles',
                        'type' => 'user',
                    ]
                ],
                'trending' => [
                    [
                        'selected' => false,
                        'value' => 'comedy',
                        'posts_count' => 32,
                        'votes_count' => 45,
                        'type' => "trending"
                    ]
                ],
                'default' => [],
            ]);

        $this->manager->getTagTrends(Argument::any())
            ->willReturn([]);

        $response = $this->getTags($request);
        $json = $response->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'tags' => [
                [
                    'selected' => true,
                    'value' => 'music',
                    'type' => 'user',
                ],
                [
                    'selected' => true,
                    'value' => 'beatles',
                    'type' => 'user',
                ]
            ],
            'trending' => [
                [
                    'selected' => false,
                    'value' => 'comedy',
                    'posts_count' => 32,
                    'votes_count' => 45,
                    'type' => "trending"
                ]
            ],
            'default' => [],
            'for_you' => null,
            'activity_related' => null,
        ]));
    }

    public function it_should_get_related_tags_response(ServerRequest $request)
    {
        $opts = [
            'wire_support_tier' => null,
            'trending_tags_v2' => false
        ];

        $this->manager->getTags($opts)
            ->willReturn([
                'tags' => [
                ],
                'trending' => [
                ],
                'default' => [],
            ]);

        $this->manager->getTagTrends(Argument::any())
            ->willReturn([]);

        $request->getQueryParams()
                ->willReturn([
                    'entity_guid' => '123',
                    'wire_support_tier' => null
                ]);

        $this->manager->getActivityRelatedTags('123')
                    ->willReturn([
                        (new Trend())
                            ->setId('id')
                            ->setHashtag('music')
                            ->setVolume(10)
                            ->setPeriod(12)
                            ->setSelected(true),
                        (new Trend())
                            ->setId('id2')
                            ->setHashtag('art')
                            ->setVolume(5)
                            ->setPeriod(24)
                            ->setSelected(false)
                    ]);

        $response = $this->getTags($request);
        $json = $response->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'tags' => [
            ],
            'trending' => [
            ],
            'default' => [],
            'for_you' => null,
            'activity_related' => [
                [
                    'id' => 'id',
                    'entity' => null,
                    'guid' => null,
                    'hashtag' => 'music',
                    'title' => "",
                    'volume' => 10,
                    'period' => 12,
                    'selected' => true,
                ],
                [
                    'id' => 'id2',
                    'entity' => null,
                    'guid' => null,
                    'hashtag' => 'art',
                    'title' => "",
                    'volume' => 5,
                    'period' => 24,
                    'selected' => false,
                ]
            ],
        ]));
    }
}
