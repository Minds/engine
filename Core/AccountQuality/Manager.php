<?php

namespace Minds\Core\AccountQuality;

use Minds\Common\Repository\Response;

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
     * @return Response
     */
    public function getAccountQualityScore(string $userId): Response
    {
        $userQualityScore = $this->repository->getAccountQualityScore($userId);

        return new Response([
            'score' => $userQualityScore
        ]);
    }
}
