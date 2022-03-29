<?php

namespace Spec\Minds\Helpers;

use Minds\Exceptions\StringLengthException;
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

    public function it_should_validate_a_null_string_when_min_is_0()
    {
        $this->validate('briefdescription', null)->shouldReturn(true);
    }

    public function it_should_validate_an_empty_string_when_min_is_0()
    {
        $this->validate('briefdescription', '')->shouldReturn(true);
    }

    public function it_should_NOT_validate_an_INVALID_string_when_under_limit()
    {
        // min 4 chars.
        $this->shouldThrow(StringLengthException::class)->duringValidate('username', 'Min');
    }

    public function it_should_NOT_validate_an_INVALID_string_when_over_limit()
    {
        // max exceeded - 51 chars +
        $this->shouldThrow(StringLengthException::class)->duringValidate('username', '01234567890123456789012345678901234567890123456789t');
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

    public function it_should_get_max_bound()
    {
        $this->getMax('username')->shouldReturn(50);
    }

    public function it_should_get_min_bound()
    {
        $this->getMin('username')->shouldReturn(4);
    }

    public function it_should_give_limits_as_string()
    {
        $this->limitsToString('username')->shouldReturn(
            "Invalid username. Must be between 4 and 50 characters."
        );
    }

    public function it_should_give_limits_as_string_when_name_override_provided()
    {
        $this->limitsToString('username', 'name')->shouldReturn(
            "Invalid name. Must be between 4 and 50 characters."
        );
    }
}
