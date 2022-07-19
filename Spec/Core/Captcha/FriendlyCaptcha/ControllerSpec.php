<?php

namespace Spec\Minds\Core\Captcha\FriendlyCaptcha;

use Minds\Core\Captcha\FriendlyCaptcha\Classes\DifficultyScalingType;
use Minds\Core\Captcha\FriendlyCaptcha\Controller;
use Minds\Core\Captcha\FriendlyCaptcha\Manager;
use Minds\Core\Router\Exceptions\ForbiddenException;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    private $manager;

    public function let(
        Manager $manager
    ) {
        $this->manager = $manager;
        $this->beConstructedWith($manager);
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_call_to_generate_puzzle()
    {
        $serverRequest = (new ServerRequest())
            ->withQueryParams([
                'origin' => DifficultyScalingType::DIFFICULTY_SCALING_VOTE_UP
            ]);

        $puzzle = 'puzzle';

        $this->manager->generatePuzzle(DifficultyScalingType::DIFFICULTY_SCALING_VOTE_UP)
            ->shouldBeCalled()
            ->willReturn($puzzle);

        $response = $this->generatePuzzle($serverRequest);
        $json = $response->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 'success',
            'data' => [
                'puzzle' => $puzzle
            ]
        ]));
    }

    public function it_should_NOT_call_to_verify_because_debug_should_be_false_on_merge(
        ServerRequest $request
    ) {
        $this->shouldThrow(ForbiddenException::class)
            ->duringVerifySolution($request);
    }
}
