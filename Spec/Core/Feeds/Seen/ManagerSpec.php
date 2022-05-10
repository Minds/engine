<?php

namespace Spec\Minds\Core\Feeds\Seen;

use Minds\Core\Data\Redis;
use Minds\Core\Feeds\Seen\Manager;
use Minds\Core\Feeds\Seen\SeenCacheKeyCookie;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
    
    public function it_should_mark_entities_as_seen_correctly(
        Redis\Client $redisClient
    ) {
        $redisClient->sAdd(Argument::type("string"), 'fakeGuid', 'fakeGuid2')->shouldBeCalledOnce();
        $redisClient->expire(Argument::any(), Argument::any())->shouldBeCalledOnce();

        $this->beConstructedWith($redisClient);

        $this->seeEntities(['fakeGuid', 'fakeGuid2']);
    }
    
    public function it_should_list_seen_entities_correctly_with_pseudo_id(
        Redis\Client $redisClient
    ) {
        $_COOKIE["minds_pseudoid"] = "pseudoid";
        $redisClient->sScan('seen-entities::pseudoid', Argument::any(), Argument::any(), Argument::any())->shouldBeCalledOnce()->willReturn(['fakeGuid']);

        $this->beConstructedWith($redisClient);

        $this->listSeenEntities()->shouldReturn(['fakeGuid']);
    }
    
    public function it_should_list_seen_entities_correctly_without_pseudo_id(
        Redis\Client $redisClient,
        SeenCacheKeyCookie $seenCacheKeyCookie,
    ) {
        $_COOKIE["minds_pseudoid"] = null;
        $redisClient->sScan('seen-entities::fakeRandomNumber', Argument::any(), Argument::any(), Argument::any())->shouldBeCalledOnce()->willReturn(['fakeGuid']);
        $seenCacheKeyCookie->getValue()->willReturn('fakeRandomNumber');
        $seenCacheKeyCookie->createCookie()->willReturn($seenCacheKeyCookie);

        $this->beConstructedWith($redisClient, $seenCacheKeyCookie);

        $this->listSeenEntities()->shouldReturn(['fakeGuid']);
    }
}
