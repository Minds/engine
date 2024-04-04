<?php

namespace Spec\Minds\Core\Feeds\RSS\Controllers;

use Minds\Core\Feeds\RSS\Controllers\Controller;
use Minds\Core\Feeds\RSS\Services\Service;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\Guid;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ControllerSpec extends ObjectBehavior
{
    private Collaborator $serviceMock;

    public function let(Service $serviceMock)
    {
        $this->beConstructedWith($serviceMock);
        $this->serviceMock = $serviceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_create_rss_feed(RssFeed $rssFeed, User $user)
    {
        $this->serviceMock->createRssFeed($rssFeed, $user)
            ->shouldBeCalled()
            ->willReturn($rssFeed);

        $this->createRssFeed($rssFeed, $user)
            ->shouldBe($rssFeed);
    }

    public function it_should_return_a_list_of_rss_feeds(User $user)
    {
        $this->serviceMock->getRssFeeds($user)
            ->willReturn([
                new RssFeed(1, Guid::build(), 'My rss feed 1', 'https://phpspec.fake/rss.xml'),
                new RssFeed(2, Guid::build(), 'My rss feed 2', 'https://phpspec.fake/rss.xml'),
            ]);

        $result = $this->getRssFeeds($user);
        $result->shouldHaveCount(2);
        $result[0]->shouldBeAnInstanceOf(RssFeed::class);
        $result[0]->feedId->shouldBe(1);
        $result[1]->shouldBeAnInstanceOf(RssFeed::class);
        $result[1]->feedId->shouldBe(2);
    }

    public function it_should_return_an_rss_feed(User $user)
    {
        $this->serviceMock->getRssFeed(1, $user)
            ->willReturn(
                new RssFeed(1, Guid::build(), 'My rss feed 1', 'https://phpspec.fake/rss.xml'),
            );

        $result = $this->getRssFeed("1", $user);
        $result->shouldBeAnInstanceOf(RssFeed::class);
        $result->feedId->shouldBe(1);
    }

    public function it_should_refresh_an_rss_feed(User $user)
    {
        $this->serviceMock->refreshRssFeed(1, $user)
            ->willReturn(
                new RssFeed(1, Guid::build(), 'My rss feed 1', 'https://phpspec.fake/rss.xml'),
            );

        $result = $this->refreshRssFeed("1", $user);
        $result->shouldBeAnInstanceOf(RssFeed::class);
        $result->feedId->shouldBe(1);
    }

    public function it_should_remove_rss_feed(User $user)
    {
        $this->serviceMock->removeRssFeed(1, $user)
            ->shouldBeCalled();

        $this->removeRssFeed("1", $user);
    }
}
