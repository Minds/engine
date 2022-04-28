<?php

namespace Spec\Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Classes\Helpers;
use PhpSpec\ObjectBehavior;

class HelpersSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Helpers::class);
    }

    public function it_should_pad_hex_right_with_0s()
    {
        $this->padHex('e03ce673', 10, STR_PAD_RIGHT)
            ->shouldBe('e03ce673000000000000');
    }

    public function it_should_pad_hex_left_with_0s()
    {
        $this->padHex('e03ce673', 10, STR_PAD_LEFT)
            ->shouldBe('000000000000e03ce673');
    }

    public function it_should_pad_hex_BOTH_SIDES_with_0s()
    {
        $this->padHex('e03ce673', 10, STR_PAD_BOTH)
            ->shouldBe('000000e03ce673000000');
    }

    public function it_should_extract_1_hex_byte_mid_hex()
    {
        $this->extractHexBytes('e03ce673', 1, 1)
            ->shouldBe('3c');
    }

    public function it_should_extract_2_hex_byte_mid_hex()
    {
        $this->extractHexBytes('e03ce673', 2, 2)
            ->shouldBe('e673');
    }

    public function it_should_get_little_endian_from_hex_and_convert_to_dec()
    {
        $this->littleEndianHexToDec('e03ce673')
            ->shouldBe(1944468704);
    }
}
