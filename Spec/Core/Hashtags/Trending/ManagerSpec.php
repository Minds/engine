<?php

namespace Spec\Minds\Core\Hashtags\Trending;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Hashtags\Trending\Manager;
use Minds\Core\Hashtags\Trending\Repository;
use Minds\Interfaces\BasicCacheInterface;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public $repository;
    public $cache;

    private Collaborator $configMock;

    public function let(
        Repository $repository,
        BasicCacheInterface $cache,
        Config $configMock,
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->configMock = $configMock;
        $this->beConstructedWith($repository, $cache, $configMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_currently_trending_tags_from_repository_using_previous_days_trending(
        Response $previouslyTrendingResponse,
        Response $currentlyTrendingResponse,
    ) {
        $previouslyTrendingTags = [
            ['tag' => 'hashtag2'],
            ['tag' => 'hashtag4']
        ];

        $currentlyTrendingTags = [
            ['tag' => 'hashtag1'],
            ['tag' => 'hashtag3'],
            ['tag' => 'hashtag5']
        ];

        $this->configMock->get('tenant_id')
            ->willReturn(null);

        $previouslyTrendingResponse->toArray()
            ->shouldBeCalled()
            ->willReturn($previouslyTrendingTags);

        $currentlyTrendingResponse->toArray()
            ->shouldBeCalled()
            ->willReturn($currentlyTrendingTags);

        $this->repository->getList(Argument::that(function ($opts) {
            return is_int($opts['from']) &&
                isset($opts['to']) &&
                is_int($opts['to']);
        }))
            ->shouldBeCalledTimes(1)
            ->willReturn($previouslyTrendingResponse);

        
        $this->repository->getList(Argument::that(function ($opts) {
            return is_int($opts['from']) &&
                isset($opts['exclude_tags']) &&
                $opts['exclude_tags'] = ['hashtag1', 'hashtag2'];
        }))
            ->shouldBeCalledTimes(1)
            ->willReturn($currentlyTrendingResponse);

        $this->configMock->get('trending_tags_development_mode')->willReturn(false);
    
        $this->cache->get()->shouldBeCalled()->willReturn([]);

        $response = [
            0 => [
             'selected' => false,
             'value' => "hashtag1",
             'posts_count' => 0,
             'votes_count' => 0,
             'type' => "trending",
           ],
           1 => [
             'selected' => false,
             'value' => "hashtag3",
             'posts_count' => 0,
             'votes_count' => 0,
             'type' => "trending",
           ],
           2 => [
             'selected' => false,
             'value' => "hashtag5",
             'posts_count' => 0,
             'votes_count' => 0,
             'type' => "trending",
           ]
        ];

        $this->cache->set($response)->shouldBeCalled();
        $this->getCurrentlyTrendingHashtags()->shouldReturn($response);
    }
}
