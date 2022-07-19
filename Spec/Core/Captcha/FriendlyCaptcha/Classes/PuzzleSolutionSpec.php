<?php

namespace Spec\Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Classes\DifficultyScalingType;
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


    public $testSolution = '4ef0b4fe5f5310e15b7e1ca0a99a5a8610020621e2bc9ee379f42d73d0079110.Yrn6jAAAAAEAAAABAQUzegAAAAAAAAAAXX8S03HhG2MAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAdm90ZV91cA==.AAAAALw8AAABAAAAMfUBAAIAAAD1eQEAAwAAADGAAAAEAAAAwBgAAAUAAAAVMwAABgAAAOJYAAAHAAAAsMAAAAgAAABjFQEACQAAAP+TAAAKAAAAwHAAAAsAAAAidQAADAAAALXBAAANAAAAe8sAAA4AAADNVAAADwAAAN97AAAQAAAAPzkAABEAAAB0RQAAEgAAAMU2AAATAAAAg08AABQAAAD6LQAAFQAAALgRAAAWAAAA1SQAABcAAADfAgAAGAAAAKrWAAAZAAAANJoBABoAAAB5kAAAGwAAAKGFAAAcAAAAEJ4AAB0AAADTFQAAHgAAAOgnAAAfAAAAtpoAACAAAADfgwAAIQAAADLMAAAiAAAA+AgAACMAAACebgAAJAAAAPyBAQAlAAAAc0wAACYAAABXYQEAJwAAAByGAQAoAAAA8qEAACkAAADTRgAAKgAAAANJAAArAAAAyOEAACwAAAB/UgEALQAAAK4PAAAuAAAA13UAAC8AAADMUwAAMAAAAMALAAAxAAAAYRIAADIAAAADMQEA.AgAB"';
    
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

        $this->puzzle->getOrigin()
            ->willReturn(DifficultyScalingType::DIFFICULTY_SCALING_VOTE_UP);

        $this->puzzle->checkHasExpired(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->puzzle);

        $this->shouldThrow(InvalidSolutionException::class)
            ->duringVerify(DifficultyScalingType::DIFFICULTY_SCALING_VOTE_UP);
    }
}
