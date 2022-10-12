<?php

namespace Spec\Minds\Core\Feeds\Supermind\Validators;

use Minds\Core\Feeds\Supermind\Validators\SupermindFeedRequestValidator;

class SupermindFeedRequestValidatorSpec extends \PhpSpec\ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SupermindFeedRequestValidator::class);
    }

    public function it_passes_validation(): void
    {
        $dataToValidate = [
            'limit' => 12
        ];

        $this->validate($dataToValidate)->shouldBe(true);
    }

    public function it_fails_validation_when_no_limit_provided(): void
    {
        $dataToValidate = [];

        $this->validate($dataToValidate)->shouldBe(false);
    }

    public function it_fails_validation_when_limit_is_not_numeric(): void
    {
        $dataToValidate = [
            'limit' => "asdasdasda"
        ];

        $this->validate($dataToValidate)->shouldBe(false);
    }

    public function it_fails_validation_when_limit_is_less_than_zero(): void
    {
        $dataToValidate = [
            'limit' => -1
        ];

        $this->validate($dataToValidate)->shouldBe(false);
    }

    public function it_fails_validation_when_limit_is_greater_than_500(): void
    {
        $dataToValidate = [
            'limit' => 501
        ];

        $this->validate($dataToValidate)->shouldBe(false);
    }
}
