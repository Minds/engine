<?php

namespace Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

/**
 *
 */
class DifficultyScalingConfig
{
    public const DEFAULT_DIFFICULTY_SCALING = [
        0 => ['solutions' => 51, 'difficulty' => 122],
        4 => ['solutions' => 51, 'difficulty' => 130],
        10 => ['solutions' => 45, 'difficulty' => 141],
        20 => ['solutions' => 45, 'difficulty' => 149],
    ];

    public function __construct(
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get("Config");
    }

    /**
     * Return the requested difficulty scaling for Friendly Captcha
     * @param string $difficultyScalingType
     *               The allowed values are in the format of `DifficultyScalingType::DIFFICULTY_SCALING_REGISTRATION`
     * @return array
     */
    public function get(string $difficultyScalingType): array
    {
        return match ($difficultyScalingType) {
            DifficultyScalingType::DIFFICULTY_SCALING_VOTE_UP =>
                $this->config->get("captcha")["friendly_captcha"]["difficulty_scaling"]["vote_up"] ?? self::DEFAULT_DIFFICULTY_SCALING,
            default => self::DEFAULT_DIFFICULTY_SCALING
        };
    }
}
