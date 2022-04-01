<?php

namespace Spec\Minds\Helpers\StringLengthValidators;

use Minds\Exceptions\StringLengthException;
use PhpSpec\ObjectBehavior;

class UsernameLengthValidatorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Helpers\StringLengthValidators\UsernameLengthValidator');
    }

    public function it_should_validate_a_valid_string_at_min_bounds()
    {
        // min 1 char
        $this->validate('M')->shouldReturn(true);
    }

    public function it_should_validate_a_valid_string_at_max_bounds()
    {
        // max 50 chars
        $this->validate('01234567890123456789012345678901234567890123456789')->shouldReturn(true);
    }

    public function it_should_NOT_validate_a_null_string()
    {
        $this->shouldThrow(StringLengthException::class)->duringValidate(null);
    }

    public function it_should_NOT_validate_an_empty_string()
    {
        $this->shouldThrow(StringLengthException::class)->duringValidate('');
    }

    public function it_should_NOT_validate_an_INVALID_string_when_over_limit()
    {
        // max exceeded - 51 chars +
        $this->shouldThrow(StringLengthException::class)->duringValidate('01234567890123456789012345678901234567890123456789t');
    }

    public function it_should_trim_a_string_to_a_max_length_when_max_length_exceeded()
    {
        $this->validateMaxAndTrim(
            '01234567890123456789012345678901234567890123456789test'
        )->shouldReturn(
            '01234567890123456789012345678901234567890123456789...'
        );

        $this->validateMaxAndTrim(
            '0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789test'
        )->shouldReturn(
            '01234567890123456789012345678901234567890123456789...'
        );
    }

    public function it_should_NOT_trim_a_string_to_a_max_length_when_max_length_NOT_exceeded()
    {
        $this->validateMaxAndTrim(
            '01234567890123456789012345678901234567890123456789'
        )->shouldReturn(
            '01234567890123456789012345678901234567890123456789'
        );

        $this->validateMaxAndTrim('01234567890')->shouldReturn('01234567890');
    }

    public function it_should_get_max_bound()
    {
        $this->getMax()->shouldReturn(50);
    }

    public function it_should_get_min_bound()
    {
        $this->getMin()->shouldReturn(1);
    }

    public function it_should_get_field_name()
    {
        $this->getFieldName()->shouldReturn('username');
    }

    public function it_should_give_limits_as_string()
    {
        $this->limitsToString()->shouldReturn(
            "Invalid username. Must be between 1 and 50 characters."
        );
    }

    public function it_should_give_limits_as_string_when_name_override_provided()
    {
        $this->limitsToString('name')->shouldReturn(
            "Invalid name. Must be between 1 and 50 characters."
        );
    }
}
