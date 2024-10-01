<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Controllers;

use Minds\Core\Admin\Services\HashtagExclusionService;
use Minds\Core\Admin\Types\HashtagExclusion\HashtagExclusionEdge;
use Minds\Core\Admin\Types\HashtagExclusion\HashtagExclusionsConnection;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Right;

/**
 * Controller for managing admin hashtag exclusions.
 */
class HashtagExclusionController
{
    public function __construct(
        private readonly HashtagExclusionService $exclusionService
    ) {
    }

    /**
     * Get hashtag exclusions.
     * @param int $first - the number of hashtags to return.
     * @param int|null $after - the timestamp to start from.
     * @return HashtagExclusionsConnection
     */
    #[Query]
    #[Logged]
    #[Right('PERMISSION_CAN_MODERATE_CONTENT')]
    public function getHashtagExclusions(
        int $first,
        int $after = null
    ): HashtagExclusionsConnection {
        $connection = new HashtagExclusionsConnection();
        $hasNextPage = false;

        $results = iterator_to_array($this->exclusionService->getExcludedHashtags(
            limit: $first,
            after: $after,
            hasNextPage: $hasNextPage
        ));

        $connection->setEdges(array_map(function ($exclusion) {
            return (new HashtagExclusionEdge($exclusion, (string) $exclusion->createdTimestamp));
        }, $results));

        $connection->setPageInfo(new PageInfo(
            hasNextPage: $hasNextPage,
            hasPreviousPage: false,
            startCursor: (string) $after ?? '',
            endCursor: (string) end($results)->createdTimestamp ?? ''
        ));

        return $connection;
    }

    /**
     * Exclude a hashtag.
     * @param string $hashtag - the hashtag to exclude.
     * @return bool - true if the hashtag was excluded, false otherwise.
     */
    #[Mutation]
    #[Logged]
    #[Right('PERMISSION_CAN_MODERATE_CONTENT')]
    public function excludeHashtag(
        string $hashtag,
        #[InjectUser] ?User $loggedInUser = null,
    ): bool {
        return $this->exclusionService->excludeHashtag(
            $hashtag,
            $loggedInUser
        );
    }

    /**
     * Remove a hashtag exclusion.
     * @param string $hashtag - the hashtag to remove the exclusion for.
     * @return bool - true if the hashtag exclusion was removed, false otherwise.
     */
    #[Mutation]
    #[Logged]
    #[Right('PERMISSION_CAN_MODERATE_CONTENT')]
    public function removeHashtagExclusion(string $hashtag): bool
    {
        return $this->exclusionService->removeHashtagExclusion($hashtag);
    }
}
