<?php

namespace Spec\Minds\Core\Feeds\Seen;

use Minds\Core\Feeds\Seen\Manager;
use Minds\Core\Feeds\Seen\Repository;
use Minds\Core\Feeds\Seen\SeenCacheKeyCookie;
use Minds\Core\Feeds\Seen\SeenEntity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $repoMock;
    private $seenCacheKeyCookieMock;

    public function let(Repository $repoMock, SeenCacheKeyCookie $seenCacheKeyCookieMock)
    {
        $this->beConstructedWith($repoMock, $seenCacheKeyCookieMock);
        $this->repoMock = $repoMock;
        $this->seenCacheKeyCookieMock = $seenCacheKeyCookieMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
    
    public function it_should_mark_entities_as_seen_correctly()
    {
        $_COOKIE["minds_pseudoid"] = "pseudoid";
        $this->repoMock->add(Argument::that(function ($seenEntity) {
            return $seenEntity->getPseudoId() === 'pseudoid'
                && $seenEntity->getEntityGuid() === 'fakeGuid';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repoMock->add(Argument::that(function ($seenEntity) {
            return $seenEntity->getPseudoId() === 'pseudoid'
                && $seenEntity->getEntityGuid() === 'fakeGuid2';
        }))
        ->shouldBeCalled()
        ->willReturn(true);

        $this->seeEntities(['fakeGuid', 'fakeGuid2']);
    }
    
    public function it_should_list_seen_entities_correctly_with_pseudo_id()
    {
        $_COOKIE["minds_pseudoid"] = "pseudoid";

        $this->repoMock->getList('pseudoid', Argument::any())
        ->willReturn([
            new SeenEntity('pseudoid', 'fakeGuid', time()),
        ]);


        $this->listSeenEntities()->shouldReturn(['fakeGuid']);
    }
    
    public function it_should_list_seen_entities_correctly_without_pseudo_id()
    {
        $_COOKIE["minds_pseudoid"] = null;

        $this->seenCacheKeyCookieMock->getValue()->willReturn('fakeRandomNumber');
        $this->seenCacheKeyCookieMock->createCookie()->willReturn($this->seenCacheKeyCookieMock);

        $this->repoMock->getList('fakeRandomNumber', Argument::any())
            ->willReturn([
                new SeenEntity('fakeRandomNumber', 'fakeGuid', time()),
            ]);


        $this->listSeenEntities()->shouldReturn(['fakeGuid']);
    }
}
