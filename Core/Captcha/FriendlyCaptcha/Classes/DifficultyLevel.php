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
 * @method DifficultyLevel setPuzzleOrigin(string $puzzleOrigin)
 */
class DifficultyLevel
{
    use MagicAttributes;

    /** @var int amount of solutions */
    private $solutions;

    /** @var int difficulty as int */
    private $difficulty;

    /**
     * Amount of attempts made already - used to init the difficulty.
     * @param ?integer $attempts - attempts made already.
     * @param string $puzzleOrigin
     * @param DifficultyScalingConfig|null $difficultyScalingConfig
     * @throws MisconfigurationException
     */
    public function __construct(
        private ?int $attempts = 0,
        private string $puzzleOrigin = "",
        private ?DifficultyScalingConfig $difficultyScalingConfig = null
    ) {
        $this->difficultyScalingConfig ??= new DifficultyScalingConfig();

        $this->init();
    }

    /**
     * Initializes solutions and difficulty values based on attempts passed in via constructor.
     * @return void
     * @throws MisconfigurationException
     */
    private function init(): void
    {
        $difficultyScaling = $this->difficultyScalingConfig->get($this->puzzleOrigin);
        foreach (array_reverse($difficultyScaling, true) as $attemptsThreshold => $scale) {
            if ($this->attempts >= $attemptsThreshold) {
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
