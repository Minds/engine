<?php
namespace Minds\Core\Captcha\FriendlyCaptcha;

use Minds\Core\Captcha\BypassManager;
use Minds\Core\Captcha\FriendlyCaptcha\Cache\AttemptsCache;
use Minds\Core\Captcha\FriendlyCaptcha\Cache\PuzzleCache;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\DifficultyLevel;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\Puzzle;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\PuzzleSigner;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\PuzzleSolution;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\PuzzleReusedException;
use Minds\Core\Di\Di;

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
     * @param string $puzzleOrigin
     * @return string generated puzzle.
     * @throws Exceptions\MisconfigurationException
     */
    public function generatePuzzle(string $puzzleOrigin): string
    {
        $this->attemptsCache->increment();

        $difficultyLevel = new DifficultyLevel(
            $this->attemptsCache->getCount(),
            $puzzleOrigin
        );

        $puzzle = (new Puzzle())
            ->setDifficultyLevel($difficultyLevel)
            ->setOrigin($puzzleOrigin)
            ->generate();

        return $puzzle;
    }

    /**
     * Verify a proposed solution string.
     * @param string $solution - proposed solution string.
     * @param string|null $expectedPuzzleOrigin
     * @return bool - true if solution is valid..
     * @throws Exceptions\InvalidSolutionException
     * @throws Exceptions\PuzzleExpiredException
     * @throws Exceptions\SignatureMismatchException
     * @throws Exceptions\SolutionAlreadySeenException
     * @throws PuzzleReusedException - if proposed puzzle solution has been reused.
     * @throws \SodiumException
     */
    public function verify(string $solution, ?string $expectedPuzzleOrigin = null): bool
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

        return $puzzleSolution->verify($expectedPuzzleOrigin);
    }
}
