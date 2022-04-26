<?php

namespace Spec\Minds\Helpers\StringLengthValidators;

use Minds\Exceptions\StringLengthException;
use PhpSpec\ObjectBehavior;

class TitleLengthValidatorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Helpers\StringLengthValidators\TitleLengthValidator');
    }

    public function it_should_validate_a_valid_string_at_min_bounds()
    {
        // min 0 char
        $this->validate('')->shouldReturn(true);
    }

    public function it_should_validate_a_valid_string_at_max_bounds()
    {
        // max 2000 chars
        $this->validate(str_repeat("a", 2000))->shouldReturn(true);
    }

    public function it_should_validate_a_null_string()
    {
        $this->validate(null)->shouldReturn(true);
    }

    public function it_should_validate_an_empty_string()
    {
        $this->validate('')->shouldReturn(true);
    }

    public function it_should_NOT_validate_an_INVALID_string_when_over_limit()
    {
        // max exceeded - 2001 chars+
        $this->shouldThrow(StringLengthException::class)->duringValidate(str_repeat("a", 2001));
    }

    public function it_should_NOT_validate_an_INVALID_string_with_multi_byte_characters()
    {
        // no mb variant for str repeat
        $testString = "A".str_repeat("❤️", 1000); // 2001 mb chars
        $this->shouldThrow(StringLengthException::class)->duringValidate($testString);
    }

    public function it_should_validate_an_valid_string_with_multi_byte_characters()
    {
        // no mb variant for str repeat
        $testString = str_repeat("❤️", 1000); // 2000 mb chars
        $this->validate($testString)->shouldBe(true);
    }

    public function it_should_correctly_validate_and_trim_multi_byte_strings()
    {
        // no mb variant for str repeat
        $testString = str_repeat("❤️", 1001); // 2002 mb chars
        $resultString = str_repeat("❤️", 1000).'...'; // 2000 mb chars
        
        $this->validateMaxAndTrim(
            $testString
        )->shouldReturn(
            $resultString
        );
    }

    public function it_should_trim_a_string_to_a_max_length_when_max_length_exceeded()
    {
        $testString = str_repeat("a", 2001);

        $this->validateMaxAndTrim(
            $testString
        )->shouldReturn(
            substr(str_repeat("a", 2001), 0, 2000) . '...'
        );
    }

    public function it_should_NOT_trim_a_string_to_a_max_length_when_max_length_NOT_exceeded()
    {
        $testString = str_repeat("a", 2000);

        $this->validateMaxAndTrim(
            $testString
        )->shouldReturn(
            $testString
        );
    }

    public function it_should_NOT_trim_an_empty_string()
    {
        $testString = '';

        $this->validateMaxAndTrim(
            $testString
        )->shouldReturn(
            $testString
        );
    }

    public function it_should_NOT_trim_a_null_string()
    {
        $testString = null;

        $this->validateMaxAndTrim(
            $testString
        )->shouldReturn(
            ''
        );
    }

    public function it_should_get_max_bound()
    {
        $this->getMax()->shouldReturn(2000);
    }

    public function it_should_get_min_bound()
    {
        $this->getMin()->shouldReturn(0);
    }

    public function it_should_get_field_name()
    {
        $this->getFieldName()->shouldReturn('title');
    }

    public function it_should_give_limits_as_string()
    {
        $this->limitsToString()->shouldReturn(
            "Invalid title. Must be between 0 and 2000 characters."
        );
    }

    public function it_should_give_limits_as_string_when_name_override_provided()
    {
        $this->limitsToString('name')->shouldReturn(
            "Invalid name. Must be between 0 and 2000 characters."
        );
    }
}
