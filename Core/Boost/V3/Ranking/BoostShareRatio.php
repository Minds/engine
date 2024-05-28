<?php
namespace Minds\Core\Boost\V3\Ranking;

use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Exceptions\ServerErrorException;

class BoostShareRatio
{
    /**
     * @throws ServerErrorException
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $guid,
        protected array $targetAudienceShares,
        protected int $targetLocation,
        protected int $targetSuitability,
    ) {
        foreach ($targetAudienceShares as $targetAudience => $share) {
            $this->setTargetAudienceShare($targetAudience, $share);
        }
    }

    /**
     * @param int $targetAudience
     * @param float $share
     * @return self
     * @throws ServerErrorException
     */
    public function setTargetAudienceShare(int $targetAudience, float $share): self
    {
        BoostTargetAudiences::validate($targetAudience);
        $this->targetAudienceShares[$targetAudience] = $share;
        return $this;
    }

    public function getTargetAudienceShare(int $targetAudience): float
    {
        return $this->targetAudienceShares[$targetAudience];
    }

    /**
     * @return int
     */
    public function getTargetLocation(): int
    {
        return $this->targetLocation;
    }

    /**
     * @return bool
     */
    public function isSafe(): bool
    {
        return $this->targetSuitability === 1;
    }
}
