<?php

namespace Spec\Minds\Helpers;

use PhpSpec\ObjectBehavior;

class StringLengthValidatorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Helpers\StringLengthValidator');
    }

    public function it_should_validate_a_valid_string()
    {
        // min 4 chars
        $this->validate('username', 'Mind')->shouldReturn(true);
        
        // max 50 chars
        $this->validate('username', '01234567890123456789012345678901234567890123456789')->shouldReturn(true);
        $this->validate('username', 'Minds')->shouldReturn(true);

        $this->validate('username', 'MindsMindsMindsMindsMindsMinds')->shouldReturn(true);
    }

    public function it_should_NOT_validate_an_INVALID_string()
    {
        // min 4 chars
        $this->validate('username', 'Min')->shouldReturn(false);
        $this->validate('username', 'Mi')->shouldReturn(false);
        $this->validate('username', 'M')->shouldReturn(false);
        $this->validate('username', '')->shouldReturn(false);
        
        // max exceeded - 51 chars +
        $this->validate('username', '01234567890123456789012345678901234567890123456789t')->shouldReturn(false);
        $this->validate('username', '0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789')->shouldReturn(false);
    }

    public function it_should_trim_a_string_to_a_max_length_when_max_length_exceeded()
    {
        $this->validateMaxAndTrim(
            'username',
            '01234567890123456789012345678901234567890123456789test'
        )->shouldReturn(
            '01234567890123456789012345678901234567890123456789...'
        );

        $this->validateMaxAndTrim(
            'username',
            '0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789test'
        )->shouldReturn(
            '01234567890123456789012345678901234567890123456789...'
        );
    }

    public function it_should_NOT_trim_a_string_to_a_max_length_when_max_length_NOT_exceeded()
    {
        $this->validateMaxAndTrim(
            'username',
            '01234567890123456789012345678901234567890123456789'
        )->shouldReturn(
            '01234567890123456789012345678901234567890123456789'
        );

        $this->validateMaxAndTrim('username', '01234567890')->shouldReturn('01234567890');
    }
}
