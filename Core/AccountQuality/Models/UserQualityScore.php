<?php

namespace Minds\Core\AccountQuality\Models;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Traits\MagicAttributes;

/**
 * Entity representing the user quality score.
 * @method float|null getScore()
 * @method self setScore(float $score)
 * @method string getCategory()
 * @method self setCategory(string $category)
 */
class UserQualityScore
{
    use MagicAttributes;
    
    private ?float $score = null;
    private string $category = "";

    public function __construct(
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get("Config");
    }

    /**
     * Checks if the score is below the risk threshold of being considered a spam account
     * @return bool
     */
    public function isBelowSpamRiskThreshold(): bool
    {
        return is_numeric($this->score) && $this->score < $this->config->get("user_quality_score")['belowSpamRiskThreshold'];
    }
}
