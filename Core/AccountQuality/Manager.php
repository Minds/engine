<?php

namespace Minds\Core\AccountQuality;

use Minds\Common\Repository\Response;

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
     * @return Response
     */
    public function getAccountQualityScore(string $userId): Response
    {
        $userQualityScore = $this->repository->getAccountQualityScore($userId);

        return new Response([
            'score' => $userQualityScore
        ]);
    }

    /**
     * Retrieves the account quality score based on the userId provided as a float.
     * @param string $userId - id of the user to get the score for.
     * @return float float value of account quality score.
     */
    public function getAccountQualityScoreAsFloat(string $userId): float
    {
        return $this->getAccountQualityScore(
            $userId
        )->toArray()['score'] ?? null;
    }
}
