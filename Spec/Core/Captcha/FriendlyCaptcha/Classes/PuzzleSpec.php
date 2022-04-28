<?php

namespace Spec\Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Classes\DifficultyLevel;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\Puzzle;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\PuzzleSigner;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\PuzzleExpiredException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PuzzleSpec extends ObjectBehavior
{
    /** @var PuzzleSigner */
    private $puzzleSigner;

    public function let(
        PuzzleSigner $puzzleSigner
    ) {
        $this->beConstructedWith($puzzleSigner);
        $this->puzzleSigner = $puzzleSigner;
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(Puzzle::class);
    }

    public function it_should_generate_a_solution()
    {
        $this->setDifficultyLevel(
            new DifficultyLevel(0)
        );
        $this->puzzleSigner->sign(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn('signature');

        $this->generate()->shouldBeValidSignature('signature.');
    }

    public function it_should_get_instance_puzzle()
    {
        $this->setPuzzle('123');
        $this->getPuzzle()->shouldBe('123');
    }

    public function it_should_get_buffer_as_raw()
    {
        $this->setBuffer('123');
        $this->as()->shouldBe('123');
    }

    public function it_should_init_from_solution()
    {
        $this->initFromSolution('sig', base64_encode('buf'));
        $this->getSignature()->shouldBe('sig');
        $this->getBuffer()->shouldBe('buf');
    }

    public function it_should_get_buffer_as_hex()
    {
        $this->setBuffer('1');
        $this->as('hex')->shouldBe('31');
    }

    public function it_should_check_has_NOT_expired()
    {
        $this->checkHasExpired('111111111110000000000000000001010')
            ->shouldBe($this);
    }

    public function it_should_throw_if_has_expired()
    {
        $this
            ->shouldThrow(PuzzleExpiredException::class)
            ->duringCheckHasExpired('000000000000000000000000001010');
    }

    public function getMatchers(): array
    {
        return [
            'beValidSignature' => function ($subject, $predictedSignatureSegment) {
                return str_contains($subject, $predictedSignatureSegment) &&
                    strlen($subject) > strlen($predictedSignatureSegment);
            },
        ];
    }
}
