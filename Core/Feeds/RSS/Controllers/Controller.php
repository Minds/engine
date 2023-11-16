<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\Controllers;

use Minds\Core\Feeds\RSS\Exceptions\RssFeedFailedFetchException;
use Minds\Core\Feeds\RSS\Exceptions\RssFeedNotFoundException;
use Minds\Core\Feeds\RSS\Services\Service;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class Controller
{
    public function __construct(
        private readonly Service $service
    ) {
    }

    /**
     * @param RssFeed $rssFeed
     * @return void
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function createRssFeed(
        RssFeed $rssFeed,
        #[InjectUser] User $loggedInUser,
    ): void {
        $this->service->createRssFeed($rssFeed, $loggedInUser);
    }

    /**
     * @return RssFeed[]
     * @throws ServerErrorException
     */
    #[Query]
    #[Logged]
    public function getRssFeeds(
        #[InjectUser] User $loggedInUser,
    ): array {
        return $this->service->getRssFeeds($loggedInUser);
    }

    /**
     * @param string $feedId
     * @return RssFeed
     * @throws RssFeedNotFoundException
     * @throws ServerErrorException
     * @throws RssFeedFailedFetchException
     */
    #[Query]
    #[Logged]
    public function getRssFeed(
        string $feedId,
        #[InjectUser] User $loggedInUser,
    ): RssFeed {
        return $this->service->getRssFeed((int) $feedId, $loggedInUser);
    }

    /**
     * @param string $feedId
     * @return RssFeed
     * @throws ServerErrorException
     * @throws RssFeedNotFoundException
     * @throws GraphQLException
     */
    #[Mutation]
    #[Logged]
    public function refreshRssFeed(
        string $feedId,
        #[InjectUser] User $loggedInUser,
    ): RssFeed {
        return $this->service->refreshRssFeed((int)$feedId, $loggedInUser);
    }

    /**
     * @param string $feedId
     * @return void
     * @throws GraphQLException
     * @throws RssFeedNotFoundException
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function removeRssFeed(
        string $feedId,
        #[InjectUser] User $loggedInUser,
    ): void {
        $this->service->removeRssFeed((int)$feedId);
    }
}
