<?php
namespace Minds\Core\Boost\V3\Ranking;

use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;

class BoostRanking
{
    protected $rankings = [
        BoostTargetAudiences::CONTROVERSIAL => 0,
        BoostTargetAudiences::SAFE => 0,
    ];

    public function __construct(
        public readonly int $tenantId,
        public readonly int $guid
    ) {
    }

    /**
     * @param int $targetAudience
     * @return int
     */
    public function getRanking(int $targetAudience): float
    {
        return $this->rankings[$targetAudience];
    }

    /**
     * @param int $targetAudience - open or safe enums
     * @param float
     * @return self
     * @throws ServerErrorException
     */
    public function setRank(int $targetAudience, float $rank): self
    {
        BoostTargetAudiences::validate($targetAudience);
    
        $this->rankings[$targetAudience] = $rank;

        return $this;
    }
}
