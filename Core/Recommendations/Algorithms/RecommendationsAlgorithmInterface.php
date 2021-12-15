<?php

namespace Minds\Core\Recommendations\Algorithms;

use Minds\Common\Repository\Response;
use Minds\Entities\User;

interface RecommendationsAlgorithmInterface
{
    public function setUser(?User $user): self;
    public function getFriendlyName(): string;
    public function getRecommendations(): Response;
}
