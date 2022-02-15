<?php

namespace Spec\Minds\Core\Hashtags\Trending;

use Minds\Core\Hashtags\Trending\Cache;
use PhpSpec\ObjectBehavior;
use Minds\Core\Data\Redis\Client as RedisClient;

class CacheSpec extends ObjectBehavior
{
    public $redis;

    public function let(
        RedisClient $redis
    ) {
        $this->redis = $redis;
        $this->beConstructedWith($redis);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Cache::class);
    }

    public function it_should_set_in_cache(

    ) {
        $dailyTrending = [
            [
                'selected' => false,
                'value' => "hashtag1",
                'posts_count' => 0,
                'votes_count' => 0,
                'type' => "trending",
            ],
            [
                'selected' => false,
                'value' => "hashtag3",
                'posts_count' => 0,
                'votes_count' => 0,
                'type' => "trending",
            ],
            [
                'selected' => false,
                'value' => "hashtag5",
                'posts_count' => 0,
                'votes_count' => 0,
                'type' => "trending",
            ]
        ];
        
        $this->redis->set(
            'hashtags:trending:daily',
            json_encode($dailyTrending),
            600
        )->shouldBeCalled();

        $this->set($dailyTrending);
    }

    public function it_should_get_from_cache(
    ) {
        $json = '[{"selected":false,"value":"hashtag1","posts_count":0,"votes_count":0,"type":"trending"},{"selected":false,"value":"hashtag3","posts_count":0,"votes_count":0,"type":"trending"},{"selected":false,"value":"hashtag5","posts_count":0,"votes_count":0,"type":"trending"}]';
        $this->redis->get('hashtags:trending:daily')->willReturn($json);
        $this->get()->shouldHaveCount(3);
    }
}
