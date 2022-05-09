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
        $redisClient->get(Argument::type("string"))->shouldBeCalledOnce()->willReturn(['anotherFakeGuid']);
        $redisClient->set(Argument::type("string"), ['anotherFakeGuid', 'fakeGuid'])->shouldBeCalledOnce();

        $this->beConstructedWith($redisClient);

        $this->seeEntities(['fakeGuid']);
    }
    
    public function it_should_list_seen_entities_correctly_with_pseudo_id(
        Redis\Client $redisClient
    ) {
        $_COOKIE["minds_pseudoid"] = "pseudoid";
        $redisClient->get('seen-entities:pseudoid')->shouldBeCalledOnce()->willReturn(['fakeGuid']);

        $this->beConstructedWith($redisClient);

        $this->listSeenEntities()->shouldReturn(['fakeGuid']);
    }
    
    public function it_should_list_seen_entities_correctly_without_pseudo_id(
        Redis\Client $redisClient,
        SeenCacheKeyCookie $seenCacheKeyCookie,
    ) {
        $_COOKIE["minds_pseudoid"] = null;
        $redisClient->get('seen-entities:fakeRandomNumber')->shouldBeCalledOnce()->willReturn(['fakeGuid']);
        $seenCacheKeyCookie->getValue()->willReturn('fakeRandomNumber');
        $seenCacheKeyCookie->createCookie()->willReturn($seenCacheKeyCookie);

        $this->beConstructedWith($redisClient, $seenCacheKeyCookie);

        $this->listSeenEntities()->shouldReturn(['fakeGuid']);
    }
}
