<?php

namespace Spec\Minds\Core\Supermind\Validators;

use Minds\Core\Supermind\Validators\SupermindCountRequestsValidator;
use Minds\Entities\ValidationError;
use PhpSpec\ObjectBehavior;
use Spec\Minds\Common\Traits\CommonMatchers;

class SupermindCountRequestsValidatorSpec extends ObjectBehavior
{
    use CommonMatchers;

    public function it_is_initializable()
    {
        $this->shouldHaveType(SupermindCountRequestsValidator::class);
    }

    public function it_should_not_add_error_for_valid_status()
    {
        $data = ['status' => 1];
        $this->validate($data)->shouldBe(true);
    }

    public function it_should_not_add_error_for_a_mull_status()
    {
        $data = ['status' => null];
        $this->validate($data)->shouldBe(true);
    }

    public function it_should_determine_defined_invalid_status_is_invalid()
    {
        $data = ['status' => 0];
        $this->validate($data)->shouldBe(false);

        $this->getErrors()->shouldContainValueLike(new ValidationError(
            "status",
            "The provided 'status' parameter is invalid"
        ));
    }
}
