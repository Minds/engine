<?php

namespace Spec\Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Classes\Puzzle;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\PuzzleSigner;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\PuzzleSolution;
use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;

class PuzzleSignerSpec extends ObjectBehavior
{
    /** @var Config */
    private $config;

    public function let(
        Config $config
    ) {
        $this->config = $config;
        $this->config->get('captcha')
            ->shouldBeCalled()
            ->willReturn([
                'friendly_captcha' => [
                        'signing_secret' => 'secret'
                ]
            ]);

        $this->beConstructedWith($config);
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(PuzzleSigner::class);
    }

    public function it_should_verify(
        PuzzleSolution $puzzleSolution,
        Puzzle $puzzle
    ) {
        $puzzle->as('binary')->willReturn('010');

        $puzzle->getSignature()
            ->shouldBeCalled()
            ->willReturn('f7c54cfc78ab2654a78c9cee38833572003f8d93755f245d8939cf196dbbb2d6');
        $puzzleSolution->getPuzzle()
            ->shouldBeCalled()
            ->willReturn($puzzle);

        $this->verify($puzzleSolution)
            ->shouldBe(true);
    }

    public function it_should_sign()
    {
        $this->sign('abc')
            ->shouldBe('9946dad4e00e913fc8be8e5d3f7e110a4a9e832f83fb09c345285d78638d8a0e');
    }
}
