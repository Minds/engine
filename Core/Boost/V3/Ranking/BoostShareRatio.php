<?php
namespace Minds\Core\Boost\V3\Ranking;

use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;

class BoostShareRatio
{
    public function __construct(
        protected string $guid,
        protected array $targetAudienceShares,
        protected int $targetLocation,
        protected int $targetSuitability,
    ) {
        foreach ($targetAudienceShares as $targetAudience => $share) {
            $this->setTargetAudienceShare($targetAudience, $share);
        }
    }

    /**
     * The guid of the boost
     * @return string
     */
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * @param int $targetAudience
     * @param float $share
     * @return self
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
