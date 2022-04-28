<?php

namespace Spec\Minds\Core\Captcha\FriendlyCaptcha\Cache;

use Minds\Core\Captcha\FriendlyCaptcha\Cache\PuzzleCache;
use Minds\Core\Data\cache\PsrWrapper;
use PhpSpec\ObjectBehavior;

class PuzzleCacheSpec extends ObjectBehavior
{
    /** @var PsrWrapper */
    private $cache;

    public function let(
        PsrWrapper $cache,
    ) {
        $this->beConstructedWith($cache);
        $this->cache = $cache;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PuzzleCache::class);
    }

    public function it_should_return_true_if_puzzle_has_been_used()
    {
        $puzzleHash = 'puzzleHash';
        $this->cache->get("friendly-captcha-puzzle:$puzzleHash")
            ->shouldBeCalled()
            ->willReturn('1');

        $this->get($puzzleHash)
            ->shouldBe(true);
    }

    public function it_should_return_false_if_puzzle_has_NOT_been_used()
    {
        $puzzleHash = 'puzzleHash';
        $this->cache->get("friendly-captcha-puzzle:$puzzleHash")
            ->shouldBeCalled()
            ->willReturn(null);

        $this->get($puzzleHash)
            ->shouldBe(false);
    }

    public function it_should_set_a_puzzle_hash_in_cache()
    {
        $puzzleHash = 'puzzleHash';
        $this->cache->set(
            "friendly-captcha-puzzle:$puzzleHash",
            1,
            $this->CACHE_TIME_SECONDS
        )
            ->shouldBeCalled();

        $this->set($puzzleHash)
            ->shouldBe($this);
    }
}
