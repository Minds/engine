<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\RSS\Services;

use DateTimeImmutable;
use Laminas\Feed\Reader\Entry\Rss as RssEntry;
use Laminas\Feed\Reader\Feed\Rss;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\RSS\Enums\RssFeedLastFetchStatusEnum;
use Minds\Core\Feeds\RSS\Exceptions\RssFeedFailedFetchException;
use Minds\Core\Feeds\RSS\Repositories\MySQLRepository;
use Minds\Core\Feeds\RSS\Services\ProcessRssFeedService;
use Minds\Core\Feeds\RSS\Services\Service;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\CommonMatchers;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use Zend\Diactoros\Uri;

class ServiceSpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $processRssFeedServiceMock;
    private Collaborator $mySQLRepositoryMock;
    private Collaborator $multiTenantBootServiceMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $loggerMock;

    public function let(
        ProcessRssFeedService $processRssFeedServiceMock,
        MySQLRepository $mySQLRepositoryMock,
        MultiTenantBootService $multiTenantBootServiceMock,
        EntitiesBuilder $entitiesBuilderMock,
        Logger $loggerMock
    ): void {
        $this->processRssFeedServiceMock = $processRssFeedServiceMock;
        $this->mySQLRepositoryMock = $mySQLRepositoryMock;
        $this->multiTenantBootServiceMock = $multiTenantBootServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith(
            $this->processRssFeedServiceMock,
            $this->mySQLRepositoryMock,
            $this->multiTenantBootServiceMock,
            $this->entitiesBuilderMock,
            $this->loggerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Service::class);
    }

    public function it_should_successfully_create_rss_feed(
        User $userMock,
        Rss $rssEntryMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $rssEntryMock->getTitle()
            ->willReturn('Test');

        $rssFeedMock = new RssFeed(
            feedId: 0,
            userGuid: 123,
            title: 'Test',
            url: 'https://test.com'
        );

        $this->processRssFeedServiceMock->getFeedDetails($rssFeedMock->url)
            ->shouldBeCalledOnce()
            ->willReturn($rssEntryMock);

        $this->mySQLRepositoryMock->createRssFeed(
            Argument::that(fn (Uri $uri): bool => (string) $uri === 'https://test.com'),
            Argument::that(fn (string $title): bool => $title === 'Test'),
            Argument::that(fn (User $user): bool => $user->getGuid() === '123')
        )
            ->shouldBeCalledOnce()
            ->willReturn($rssFeedMock);

        $this->createRssFeed($rssFeedMock, $userMock)
            ->shouldReturn($rssFeedMock);
    }

    public function it_should_throw_graphql_exception_whilst_creating_rss_feed_when_create_rss_feed_fails(
        User $userMock,
        Rss $rssEntryMock
    ): void {
        $rssFeedMock = new RssFeed(
            feedId: 0,
            userGuid: 123,
            title: 'Test',
            url: 'https://test.com'
        );

        $rssEntryMock->getTitle()
            ->willReturn('Test');

        $userMock->getGuid()
            ->willReturn('123');

        $this->processRssFeedServiceMock->getFeedDetails($rssFeedMock->url)
            ->shouldBeCalledOnce()
            ->willReturn($rssEntryMock);

        $this->mySQLRepositoryMock->createRssFeed(
            Argument::type(Uri::class),
            Argument::type('string'),
            Argument::type(User::class)
        )
            ->shouldBeCalledOnce()
            ->willThrow(ServerErrorException::class);

        $this->shouldThrow(GraphQLException::class)
            ->during('createRssFeed', [$rssFeedMock, $userMock]);
    }

    public function it_should_throw_failed_fetch_exception_whilst_creating_rss_feed_when_rss_feed_url_cannot_be_fetched(
        User $userMock
    ): void {
        $rssFeedMock = new RssFeed(
            feedId: 0,
            userGuid: 123,
            title: 'Test',
            url: 'https://test.com'
        );

        $this->processRssFeedServiceMock->getFeedDetails(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willThrow(RssFeedFailedFetchException::class);

        $this->shouldThrow(RssFeedFailedFetchException::class)
            ->during('createRssFeed', [$rssFeedMock, $userMock]);
    }

    public function it_should_get_rss_feed(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $rssFeedMock = new RssFeed(
            feedId: 0,
            userGuid: 123,
            title: 'Test',
            url: 'https://test.com'
        );

        $this->mySQLRepositoryMock->getFeed(0)
            ->shouldBeCalledOnce()
            ->willReturn($rssFeedMock);

        $this->processRssFeedServiceMock->fetchFeed($rssFeedMock)
            ->shouldBeCalledOnce()
            ->willYield([$rssFeedMock]);

        $this->getRssFeed(0, $userMock)
            ->shouldReturn($rssFeedMock);
    }

    public function it_should_throw_an_exception_if_rss_feed_owner_diff_from_request_user_when_get_rss_feed(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $rssFeedMock = new RssFeed(
            feedId: 0,
            userGuid: 124,
            title: 'Test',
            url: 'https://test.com'
        );

        $this->mySQLRepositoryMock->getFeed(0)
            ->shouldBeCalledOnce()
            ->willReturn($rssFeedMock);

        $this->shouldThrow(GraphQLException::class)
            ->during('getRssFeed', [0, $userMock]);
    }

    public function it_should_get_user_rss_feeds(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $rssFeedMock = new RssFeed(
            feedId: 0,
            userGuid: 124,
            title: 'Test',
            url: 'https://test.com'
        );

        $this->mySQLRepositoryMock->getFeeds(
            Argument::that(fn (User $user): bool => $user->getGuid() === '123')
        )
            ->shouldBeCalledOnce()
            ->willYield([$rssFeedMock]);

        $this->getRssFeeds($userMock)->shouldContainValueLike($rssFeedMock);
    }

    public function it_should_remove_rss_feed(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $rssFeedMock = new RssFeed(
            feedId: 0,
            userGuid: 123,
            title: 'Test',
            url: 'https://test.com'
        );

        $this->mySQLRepositoryMock->getFeed(0)
            ->shouldBeCalledOnce()
            ->willReturn($rssFeedMock);

        $this->mySQLRepositoryMock->removeRssFeed(0)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->removeRssFeed(0, $userMock)
            ->shouldReturn(true);
    }

    public function it_should_throw_an_exception_if_rss_feed_owner_diff_from_request_user_when_remove_rss_feed(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $rssFeedMock = new RssFeed(
            feedId: 0,
            userGuid: 124,
            title: 'Test',
            url: 'https://test.com'
        );

        $this->mySQLRepositoryMock->getFeed(0)
            ->shouldBeCalledOnce()
            ->willReturn($rssFeedMock);

        $this->shouldThrow(GraphQLException::class)
            ->during('removeRssFeed', [0, $userMock]);
    }



    public function it_should_refresh_rss_feed(
        User              $userMock,
        RssEntry          $rssEntryMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $entryTimestamp = time();

        $rssFeedMock = new RssFeed(
            feedId: 0,
            userGuid: 123,
            title: 'Test',
            url: 'https://test.com'
        );

        $rssEntryMock->getDateModified()
            ->willReturn(DateTimeImmutable::createFromFormat('U', (string) $entryTimestamp));

        $rssEntryMock->getTitle()
            ->willReturn('Test 1');
        $rssEntryMock->getLink()
            ->willReturn('https://test.com');

        $this->mySQLRepositoryMock->getFeed(0)
            ->shouldBeCalledTimes(2)
            ->willReturn($rssFeedMock);

        $this->mySQLRepositoryMock->updateRssFeedStatus(
            0,
            RssFeedLastFetchStatusEnum::FETCH_IN_PROGRESS
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->processRssFeedServiceMock->fetchFeed($rssFeedMock)
            ->willYield([$rssEntryMock->getWrappedObject()]);

        $this->processRssFeedServiceMock->processActivitiesForFeed(
            $rssEntryMock,
            $userMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->mySQLRepositoryMock->updateRssFeed(
            Argument::that(fn (int $feedId): bool => $feedId === 0),
            null,
            RssFeedLastFetchStatusEnum::SUCCESS
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->refreshRssFeed(0, $userMock);
    }
}
