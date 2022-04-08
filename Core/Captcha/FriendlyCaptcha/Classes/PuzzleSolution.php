<?php

namespace Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\InvalidSolutionException;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\SolutionAlreadySeenException;
use Minds\Traits\MagicAttributes;

/**
 * PuzzleSolution - holds a solution to a puzzle.
 * @method Puzzle getPuzzle()
 * @method self setExtractedSolutions(string $solutions)
 * @method self setDiagnostics(string $diagnostics)
 * @method string getDiagnostics()
 */
class PuzzleSolution
{
    use MagicAttributes;

    private string $extractedSolutions;

    /**
     * PuzzleSolution constructor.
     * @param $solution - proposed solution to puzzle.
     * @param ?PuzzleSigner $puzzleSigner - signer for puzzle.
     * @throws InvalidSolutionException - if proposed encrypted solution string.
     * does cannot be parsed to yield puzzle and solutions.
     */
    public function __construct(
        $solution = null,
        private ?PuzzleSigner $puzzleSigner = null,
        private ?Puzzle $puzzle = null,
    ) {
        $this->puzzleSigner ??= new PuzzleSigner();
        $this->puzzle ??= new Puzzle();

        [$signature, $buffer, $solutions, $diagnostics] = explode('.', $solution);

        $this->puzzle->initFromSolution($signature, $buffer);

        $this->setExtractedSolutions($solutions);

        if ($diagnostics) {
            $this->setDiagnostics($diagnostics);
        }
    
        if (
            !$this->puzzle ||
            !$this->extractedSolutions
        ) {
            throw new InvalidSolutionException('Unable to parse solution');
        }
    }

    /**
     * Gets extracted solutions.
     * @param ?string $format - 'hex' or null for raw extracted solutions.
     * @return string extracted solutions.
     */
    public function getExtractedSolutions(?string $format = null): string
    {
        switch ($format) {
            case 'hex':
                return bin2hex(base64_decode($this->extractedSolutions, true));
                break;
            default:
                return $this->extractedSolutions;
        }
    }

    /**
     * Extracts and counts solutions in puzzle solution.
     * @return integer - count of extracted puzzles.
     */
    public function countSolutions(): int
    {
        return hexdec(Helpers::extractHexBytes($this->puzzle->as('hex'), 14, 1));
    }

    /**
     * Verify a puzzle solution.
     * @throws SolutionAlreadySeenException - if solution has already been seen.
     * @throws InvalidSolutionException - if solution is invalid.
     * @return boolean - true if solution is valid. Will throw if invalid.
     */
    public function verify(): bool
    {
        /** @throws SignatureMismatchException **/
        $this->puzzleSigner->verify($this);

        $puzzle = $this->puzzle;
        $puzzleHex = $puzzle->as('hex');

        $puzzle->checkHasExpired($puzzleHex);

        $difficultyThreshold = $this->getDifficultyThreshold($puzzleHex);

        $numberOfSolutions = $this->countSolutions();
        $solutionSeenInThisRequest = [];

        for ($solutionIndex = 0; $solutionIndex < $numberOfSolutions; $solutionIndex++) {
            $currentSolution = Helpers::extractHexBytes($this->getExtractedSolutions('hex'), $solutionIndex * 8, 8);

            if (isset($solutionSeenInThisRequest[$currentSolution])) {
                throw new SolutionAlreadySeenException();
            }

            $solutionSeenInThisRequest[$currentSolution] = true;

            $fullSolution = Helpers::padHex($puzzleHex, 120, STR_PAD_RIGHT) . $currentSolution;

            $blake2b256hash = bin2hex(sodium_crypto_generichash(hex2bin($fullSolution), '', 32));

            $first4Bytes = Helpers::extractHexBytes($blake2b256hash, 0, 4);
            $first4Int = Helpers::littleEndianHexToDec($first4Bytes);

            if ($first4Int >= $difficultyThreshold) {
                throw new InvalidSolutionException();
            }
        }
        return true;
    }

    /**
     * Gets difficulty threshold for given puzzle hex.
     * @param string $puzzleHex - puzzle hex to get difficulty threshold for.
     * @return int - difficulty threshold.
     */
    private function getDifficultyThreshold(string $puzzleHex): int
    {
        $difficulty = hexdec(Helpers::extractHexBytes($puzzleHex, 15, 1));
        return floor(pow(2, (255.999 - $difficulty) / 8.0));
    }
}
