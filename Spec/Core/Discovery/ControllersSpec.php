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

        $this->manager->getSearch('hello world', 'top', '')
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

    public function it_should_get_tagsd_response(ServerRequest $request)
    {
        $this->manager->getTags()
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
            ]);

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
            ]
        ]));
    }
}
