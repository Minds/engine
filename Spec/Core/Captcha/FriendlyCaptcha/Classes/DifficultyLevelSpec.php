<?php

namespace Spec\Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Manager;
use Minds\Core\Captcha\FriendlyCaptcha\Classes\DifficultyLevel;
use PhpSpec\ObjectBehavior;

class DifficultyLevelSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(DifficultyLevel::class);
    }

    public function it_should_init_correctly_with_no_previous_attempts()
    {
        $this->beConstructedWith(0);
        $this->getSolutions()->shouldBe(51);
        $this->getDifficulty()->shouldBe(122);
    }
    
    public function it_should_init_correctly_with_4_previous_attempts()
    {
        $this->beConstructedWith(4);
        $this->getSolutions()->shouldBe(51);
        $this->getDifficulty()->shouldBe(130);
    }

    public function it_should_init_correctly_with_10_previous_attempts()
    {
        $this->beConstructedWith(10);
        $this->getSolutions()->shouldBe(45);
        $this->getDifficulty()->shouldBe(141);
    }

    public function it_should_init_correctly_with_20_previous_attempts()
    {
        $this->beConstructedWith(20);
        $this->getSolutions()->shouldBe(45);
        $this->getDifficulty()->shouldBe(149);
    }

    public function it_should_init_correctly_with_100_previous_attempts()
    {
        $this->beConstructedWith(100);
        $this->getSolutions()->shouldBe(45);
        $this->getDifficulty()->shouldBe(149);
    }
}
