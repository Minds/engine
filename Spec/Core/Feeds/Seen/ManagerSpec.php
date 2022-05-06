<?php

namespace Spec\Minds\Core\Feeds\Seen;

use Minds\Common\Repository\Response;
use Minds\Core\Data\cache\Redis;
use Minds\Core\Feeds\Seen\Manager;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Feeds\Seen\SeenCacheKeyCookie;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
    
    public function it_should_mark_entities_as_seen_correctly(
        Redis $redisClient
    ) {
        $redisClient->get(Argument::type("string"))->shouldBeCalledOnce()->willReturn(['anotherFakeGuid']);
        $redisClient->set(Argument::type("string"), ['anotherFakeGuid', 'fakeGuid'])->shouldBeCalledOnce();

        $this->beConstructedWith($redisClient);

        $this->seeEntities(['fakeGuid']);
    }
    
    public function it_should_list_seen_entities_correctly_with_pseudo_id(
        Redis $redisClient
    ) {
        $_COOKIE["minds_pseudoid"] = "pseudoid";
        $redisClient->get('seen-entities:pseudoid')->shouldBeCalledOnce()->willReturn(['fakeGuid']);

        $this->beConstructedWith($redisClient);

        $this->listSeenEntities()->shouldReturn(['fakeGuid']);
    }
    
    public function it_should_list_seen_entities_correctly_without_pseudo_id(
        Redis $redisClient,
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
