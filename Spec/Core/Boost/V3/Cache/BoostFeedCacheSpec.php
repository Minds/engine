<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3\Cache;

use Minds\Core\Boost\V3\Cache\BoostFeedCache;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class BoostFeedCacheSpec extends ObjectBehavior
{
    private Collaborator $cacheMock;
    private Collaborator $loggerMock;

    public function let(PsrWrapper $cacheMock, Logger $loggerMock)
    {
        $this->cacheMock = $cacheMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith($this->cacheMock, $this->loggerMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostFeedCache::class);
    }

    public function it_should_get_from_cache()
    {
        $cacheKey = "boost-feed:12:0:1:0:234:0:1:0:123";
        $cachedValue = serialize([
            'boosts' => [['guid' => '123']],
            'hasNext' => true
        ]);

        $this->cacheMock->get($cacheKey)
            ->willReturn($cachedValue);

        $result = $this->get(
            limit: 12,
            offset: 0,
            targetStatus: 1,
            forApprovalQueue: false,
            targetUserGuid: '234',
            orderByRanking: false,
            targetAudience: 1,
            targetLocation: 0,
            loggedInUserGuid: '123'
        );

        $result->shouldBe([['guid' => '123']]);
    }

    public function it_should_return_null_when_cache_miss()
    {
        $this->cacheMock->get(Argument::any())
            ->willReturn(null);

        $result = $this->get(
            limit: 12,
            offset: 0,
            targetStatus: 1,
            forApprovalQueue: false,
            targetUserGuid: null,
            orderByRanking: false,
            targetAudience: 1,
            targetLocation: 0,
            loggedInUserGuid: '123'
        );

        $result->shouldBe(null);
    }

    public function it_should_set_in_cache()
    {
        $value = serialize([
            'boosts' => [['guid' => '123']],
            'hasNext' => true
        ]);

        $this->cacheMock->set("boost-feed:12:0:1:1::1:1:0:123", $value, 1)
            ->willReturn(true);

        $this->set(
            limit: 12,
            offset: 0,
            targetStatus: 1,
            forApprovalQueue: true,
            targetUserGuid: null,
            orderByRanking: true,
            targetAudience: 1,
            targetLocation: 0,
            loggedInUserGuid: '123',
            boosts: [['guid' => '123']],
            hasNext: true
        )->shouldBe(true);
    }

    public function it_should_build_cache_key()
    {
        $this->buildCacheKey(
            limit: 12,
            offset: 0,
            targetStatus: 1,
            forApprovalQueue: false,
            targetUserGuid: null,
            orderByRanking: false,
            targetAudience: 1,
            targetLocation: 0,
            loggedInUserGuid: '123'
        )->shouldBe('boost-feed:12:0:1:0::0:1:0:123');
    }
}
