<?php

namespace Spec\Minds\Core\Hashtags\Trending;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Hashtags\Trending\Cache;
use PhpSpec\ObjectBehavior;

class CacheSpec extends ObjectBehavior
{
    public $redis;

    public function let(
        PsrWrapper $redis
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
            $dailyTrending,
            600
        )->shouldBeCalled();

        $this->set($dailyTrending);
    }

    public function it_should_get_from_cache(
    ) {
        $json = json_decode('[{"selected":false,"value":"hashtag1","posts_count":0,"votes_count":0,"type":"trending"},{"selected":false,"value":"hashtag3","posts_count":0,"votes_count":0,"type":"trending"},{"selected":false,"value":"hashtag5","posts_count":0,"votes_count":0,"type":"trending"}]');
        $this->redis->get('hashtags:trending:daily')->willReturn($json);
        $this->get()->shouldHaveCount(3);
    }
}
