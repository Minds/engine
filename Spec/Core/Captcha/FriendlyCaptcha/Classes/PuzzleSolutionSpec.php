<?php

namespace Spec\Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Classes\Puzzle;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\PuzzleSigner;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\PuzzleSolution;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\InvalidSolutionException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PuzzleSolutionSpec extends ObjectBehavior
{
    /** @var PuzzleSigner */
    private $puzzleSigner;

    /** @var Puzzle */
    private $puzzle;

    public $testSolution = 'b8a95e2d33038668b3d88f38685dad54a0690b0136502e6e85d085bd40adcd65.YlBKogAAAAEAAAABAQUtlQAAAAAAAAAA5xykMYhG+UA=.AAAAANwIDQABAAAAcZUHAAIAAADgMwwAAwAAAD6jBwAEAAAAl/8JAAUAAAB4ugkABgAAAL8CAwAHAAAAM9wAAAgAAACTLAMACQAAAJOqMgAKAAAASwgiAAsAAADT4gAADAAAAPMdIQANAAAASgQEAA4AAAALcgIADwAAACJZCwAQAAAA4r4YABEAAAA6BQAAEgAAAA3kAAATAAAAB1wDABQAAACVpQEAFQAAAMzHAAAWAAAAFlEQABcAAACi2BIAGAAAAB98AgAZAAAAjnsEABoAAAAeXgUAGwAAAKo+BgAcAAAA+n0FAB0AAACmhhcAHgAAAN1xCwAfAAAAssUAACAAAADpBwMAIQAAABJtAwAiAAAA7poAACMAAABJ+QUAJAAAAFC+AwAlAAAAozAAACYAAAAkvgIAJwAAANwNDgAoAAAAb0AWACkAAADSfwAAKgAAANq+BgArAAAA95UCACwAAADZbgAA.AgAD';
    
    public function let(
        PuzzleSigner $puzzleSigner,
        Puzzle $puzzle
    ) {
        $this->puzzleSigner = $puzzleSigner;
        $this->puzzle = $puzzle;
        
        [$signature, $buffer] = explode('.', $this->testSolution);

        $this->puzzle->initFromSolution($signature, $buffer)
            ->shouldBeCalled()
            ->willReturn($this->puzzle);

        $this->beConstructedWith($this->testSolution, $puzzleSigner, $puzzle);
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(PuzzleSolution::class);
    }

    public function it_should_get_extracted_solutions()
    {
        $rawSolutions = 'raw';
        $this->setExtractedSolutions($rawSolutions);
        $this->getExtractedSolutions()
            ->shouldBe($rawSolutions);
    }

    public function it_should_get_extracted_solutions_as_hex()
    {
        $rawSolutions = 'raw';
        $this->setExtractedSolutions($rawSolutions);
        $this->getExtractedSolutions('hex')
            ->shouldBe(bin2hex(base64_decode($rawSolutions, true)));
    }

    public function it_should_get_count_of_1_solutions()
    {
        $this->puzzle->as('hex')->shouldBeCalled()->willReturn('11111111111111111111111111112');
        $this->countSolutions()->shouldBe(2);
    }
    
    public function it_should_get_count_of_15_solutions()
    {
        $this->puzzle->as('hex')->shouldBeCalled()->willReturn('1111111111111111111111111111f');
        $this->countSolutions()->shouldBe(15);
    }

    public function it_should_throw_for_an_invalid_solution()
    {
        [$signature, $buffer] = explode('.', $this->testSolution);

        $this->puzzleSigner->verify($this)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->puzzle->as('hex')
            ->shouldBeCalledTimes(2)
            ->willReturn(bin2hex($buffer));

        $this->puzzle->checkHasExpired(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->puzzle);

        $this->shouldThrow(InvalidSolutionException::class)
            ->duringVerify();
    }
}
