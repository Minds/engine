<?php

namespace Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\MisconfigurationException;
use Minds\Traits\MagicAttributes;

/**
 * DifficultyLevel - used to calculate the difficulty level of puzzle.
 * Can be scaled using the DIFFICULTY_SCALING such that the more attempts
 * made, the harder the puzzle gets to solve.
 * @method int getSolutions()
 * @method DifficultyLevel setSolutions(int $solutions)
 * @method int getDifficulty()
 * @method DifficultyLevel setDifficulty(int $difficulty)
 * @method DifficultyLevel setAttempts(int $attempts)
 */
class DifficultyLevel
{
    use MagicAttributes;

    // scaling for puzzle difficulty in format 'attempts => [int solutions, int difficulty]'.
    const DIFFICULTY_SCALING = [
        0 => ['solutions' => 51, 'difficulty' => 122],
        4 => ['solutions' => 51, 'difficulty' => 130],
        10 => ['solutions' => 45, 'difficulty' => 141],
        20 => ['solutions' => 45, 'difficulty' => 149],
    ];

    /**
     * Amount of attempts made already - used to init the difficulty.
     * @param integer|null $attempts - attempts made already.
     */
    public function __construct(
        private ?int $attempts = 0,
    ) {
        $this->init();
    }

    /**
     * Initializes solutions and difficulty values based on attempts passed in via constructor.
     * @return void
     */
    private function init(): void
    {
        foreach (array_reverse(self::DIFFICULTY_SCALING, true) as $attemptsThreshold => $scale) {
            if ($this->attempts > $attemptsThreshold) {
                $this->setSolutions($scale['solutions']);
                $this->setDifficulty($scale['difficulty']);
                break;
            }
        }

        if (!$this->getSolutions() || !$this->getDifficulty()) {
            throw new MisconfigurationException('Unable to parse difficulty level from configuration');
        }
    }
}
