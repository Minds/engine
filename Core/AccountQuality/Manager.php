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

    public function getAccountQualityScore(string $userId): Response
    {
        $userQualityScore = $this->repository->getAccountQualityScore($userId);

        return new Response([
            'score' => $userQualityScore
        ]);
    }
}
