<?php

namespace Minds\Core\AccountQuality;

interface RepositoryInterface
{
    public function getAccountQualityScores(?array $userIds = null): array;
    public function getAccountQualityScore(string $userId): int;
}
