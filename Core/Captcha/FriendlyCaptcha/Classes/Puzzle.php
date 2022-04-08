<?php

namespace Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\MisconfigurationException;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\PuzzleExpiredException;
use Minds\Traits\MagicAttributes;

/**
 * Puzzle object.
 * @method string getSignature()
 * @method self setSignature(string $signature)
 * @method string getBuffer()
 * @method self setBuffer(string $buffer)
 * @method self setDifficultyLevel(DifficultyLevel $difficultyLevel)
 */
class Puzzle
{
    use MagicAttributes;

    // instance puzzle string.
    private string $puzzle = '';

    // difficulty level of puzzle.
    private DifficultyLevel $difficultyLevel;

    // signature of puzzle.
    private string $signature = '';

    // unsigned puzzle buffer.
    private string $buffer = '';

    // time for puzzle expiry.
    const EXPIRE_TIME = 60;
    
    // account id.
    const ACCOUNT_ID = 1;

    // app id.
    const APP_ID = 1;

    // puzzle version.
    const PUZZLE_VERSION = 1;

    /**
     * Puzzle constructor.
     * @param ?PuzzleSigner $puzzleSigner - signer class for puzzles.
     */
    public function __construct(
        private ?PuzzleSigner $puzzleSigner = null
    ) {
        $this->puzzleSigner ??= new PuzzleSigner();
    }

    /**
     * Construct a puzzle from a solutions signature and buffer.
     * @param string $signature - signature of puzzle.
     * @param string $buffer - buffer of puzzle.
     * @return void
     */
    public function initFromSolution(string $signature, string $buffer): self
    {
        $this->setSignature($signature);
        $this->setBuffer(base64_decode($buffer, true));
        return $this;
    }

    /**
     * Generate a solvable puzzle - store in $this->puzzle.
     * @return string returns generated puzzle.
     */
    public function generate(): string
    {
        if (!$this->difficultyLevel) {
            throw new MisconfigurationException('Difficulty level not set');
        }

        $nonce = random_bytes(8);
        $timeHex = dechex(time());
        $accountIdHex = Helpers::padHex(dechex(self::ACCOUNT_ID), 4);
        $appIdHex = Helpers::padHex(dechex(self::APP_ID), 4);
        $puzzleVersionHex = Helpers::padHex(dechex(self::APP_ID), 1);

        $expireTime5MinIncrements = self::EXPIRE_TIME / 12;
        $puzzleExpiryHex = Helpers::padHex(dechex($expireTime5MinIncrements), 1);
        $numberOfSolutionsHex = Helpers::padHex(dechex($this->difficultyLevel->getSolutions()), 1);
        $puzzleDifficultyHex = Helpers::padHex(dechex($this->difficultyLevel->getDifficulty()), 1);
        $reservedHex = Helpers::padHex('', 8);
        $puzzleNonceHex = Helpers::padHex(bin2hex($nonce), 8);
        
        $bufferHex = Helpers::padHex($timeHex, 4) . $accountIdHex . $appIdHex . $puzzleVersionHex . $puzzleExpiryHex . $numberOfSolutionsHex . $puzzleDifficultyHex . $reservedHex . $puzzleNonceHex;
        
        $this->buffer = hex2bin($bufferHex);
        $this->signature = $this->puzzleSigner->sign($this->buffer);
        
        $this->puzzle = $this->signature . '.' . base64_encode($this->buffer);
        return $this->puzzle;
    }

    /**
     * Gets puzzle string.
     * @return string puzzle string.
     */
    public function get(): string
    {
        return $this->puzzle;
    }

    /**
     * Gets puzzle buffer in different formats.
     * @param ?string $format - format to return buffer in.
     * 'hex'. null will return raw buffer.
     * @return string - puzzle buffer in requested format.
     */
    public function as(?string $format = null): string
    {
        switch ($format) {
            case 'hex':
                return bin2hex($this->buffer);
                break;
            default:
                return $this->buffer;
        }
    }

    /**
     * Checks whether puzzle has expired.
     * @param string $puzzleHex - hex of puzzle.
     * @throws PuzzleExpiredException - exception thrown if puzzle has expired.
     * @return self
     */
    public function checkHasExpired(string $puzzleHex): self
    {
        $time = hexdec(Helpers::extractHexBytes($puzzleHex, 0, 4));
        $expiry = hexdec(Helpers::extractHexBytes($puzzleHex, 13, 1));
        if ($expiry !== 0) {
            if ((time() - $time) > ($expiry * 300)) {
                throw new PuzzleExpiredException();
            }
        }
        return $this;
    }
}
