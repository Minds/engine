<?php
namespace Minds\Core\Captcha\FriendlyCaptcha;

use Minds\Core\Captcha\BypassManager;
use Minds\Core\Di\Di;
use Minds\Core\Captcha\FriendlyCaptcha\Cache\AttemptsCache;
use Minds\Core\Captcha\FriendlyCaptcha\Cache\PuzzleCache;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\DifficultyLevel;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\Puzzle;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\PuzzleSigner;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\PuzzleSolution;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\PuzzleReusedException;

/**
 * Manager than handles the orchestration of business logic for FriendlyCaptcha
 * puzzle generation and solution validation.
 */
class Manager
{
    /**
     * FriendlyCaptcha Manager constructor.
     * @param ?AttemptsCache $attemptsCache - cache storing the amount of attempts made.
     * @param ?PuzzleCache $puzzleCache - cache storing expired puzzles.
     * @param ?PuzzleSigner $puzzleSigner - signer of puzzle buffers.
     */
    public function __construct(
        private ?AttemptsCache $attemptsCache = null,
        private ?PuzzleCache $puzzleCache = null,
        private ?PuzzleSigner $puzzleSigner = null,
        private ?BypassManager $bypassManager = null
    ) {
        $this->attemptsCache ??= Di::_()->get('FriendlyCaptcha\AttemptsCache');
        $this->puzzleCache ??= Di::_()->get('FriendlyCaptcha\PuzzleCache');
        $this->puzzleSigner ??= new PuzzleSigner();
        $this->bypassManager ??= new BypassManager();
    }

    /**
     * Generates puzzle and returns it for consumption by widget.
     * @throws MisconfigurationException - if server misconfigured.
     * @return string generated puzzle.
     */
    public function generatePuzzle(): string
    {
        $this->attemptsCache->increment();

        $difficultyLevel = new DifficultyLevel(
            $this->attemptsCache->getCount()
        );

        $puzzle = (new Puzzle())
            ->setDifficultyLevel($difficultyLevel)
            ->generate();

        return $puzzle;
    }

    /**
     * Verify a proposed solution string.
     * @param string $solution - proposed solution string.
     * @throws SolutionAlreadySeenException - if individual solution has already been seen.
     * @throws PuzzleReusedException - if proposed puzzle solution has been reused.
     * @throws InvalidSolutionException - if solution is invalid.
     * @return PuzzleSolution - true if solution is valid..
     */
    public function verify(string $solution): bool
    {
        if (isset($_COOKIE['captcha_bypass'])) {
            return $this->bypassManager->verify($solution);
        }

        $puzzleSolution = new PuzzleSolution($solution);

        $puzzle = $puzzleSolution->getPuzzle();
        $puzzleHex = $puzzle->as('hex');

        if ($this->puzzleCache->get($puzzleHex)) {
            throw new PuzzleReusedException();
        }

        $this->puzzleCache->set($puzzleHex);

        return $puzzleSolution->verify();
    }
}
