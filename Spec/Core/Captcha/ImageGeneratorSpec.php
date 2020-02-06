<?php

namespace Spec\Minds\Core\Captcha;

use Minds\Core\Captcha\ImageGenerator;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ImageGeneratorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ImageGenerator::class);
    }
}
