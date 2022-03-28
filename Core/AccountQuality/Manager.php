<?php

namespace Minds\Core\AccountQuality;

use Minds\Core\AccountQuality\Models\UserQualityScore;

/**
 * Responsible for the business logic in order to retrieve the relevant details required to the controller
 */
class Manager implements ManagerInterface
{
    public function __construct(
        private ?RepositoryInterface $repository = null
    ) {
        $this->repository = $this->repository ?? new Repository();
    }

    /**
     * Retrieves the account quality score based on the userId provided
     * @param string $userId
     * @return UserQualityScore
     */
    public function getAccountQualityScore(string $userId): UserQualityScore
    {
        return $this->repository->getAccountQualityScore($userId);
    }

    /**
     * Retrieves the account quality score based on the userId provided as a float.
     * @param string $userId - id of the user to get the score for.
     * @return float float value of account quality score.
     */
    public function getAccountQualityScoreAsFloat(string $userId): float
    {
        return $this->repository->getAccountQualityScore($userId)->getScore();
    }
}
