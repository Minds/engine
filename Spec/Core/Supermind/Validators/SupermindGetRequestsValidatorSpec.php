<?php

namespace Spec\Minds\Core\Supermind\Validators;

use Minds\Core\Supermind\Validators\SupermindGetRequestsValidator;
use Minds\Entities\ValidationError;
use PhpSpec\ObjectBehavior;
use Spec\Minds\Common\Traits\CommonMatchers;

class SupermindGetRequestsValidatorSpec extends ObjectBehavior
{
    use CommonMatchers;

    public function it_is_initializable()
    {
        $this->shouldHaveType(SupermindGetRequestsValidator::class);
    }

    public function it_should_validate_a_valid_request()
    {
        $data = [
            'status' => 1,
            'limit' => 12,
            'offset' => 24
        ];
        $this->validate($data)->shouldBe(true);
    }

    public function it_should_determine_data_is_invalid_if_status_is_null()
    {
        $data = [
            'limit' => 12,
            'offset' => 24
        ];
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

    public function it_should_determine_data_is_invalid_if_limit_is_not_set()
    {
        $data = [
            'status' => 1,
            'offset' => 24
        ];

        $this->validate($data)->shouldBe(false);

        $this->getErrors()->shouldContainValueLike(new ValidationError(
            "limit",
            "The 'limit' parameter must be provided and have a minimum value of '1'"
        ));
    }

    public function it_should_determine_data_is_invalid_if_limit_is_less_than_1()
    {
        $data = [
            'status' => 1,
            'limit' => 0,
            'offset' => 24
        ];

        $this->validate($data)->shouldBe(false);

        $this->getErrors()->shouldContainValueLike(new ValidationError(
            "limit",
            "The 'limit' parameter must be provided and have a minimum value of '1'"
        ));
    }

    public function it_should_determine_data_is_invalid_if_offset_is_not_set()
    {
        $data = [
            'status' => 1,
            'limit' => 24
        ];

        $this->validate($data)->shouldBe(false);

        $this->getErrors()->shouldContainValueLike(new ValidationError(
            "offset",
            "The 'offset' parameter must be provided and have a minimum value of '0' (zero)"
        ));
    }

    public function it_should_determine_data_is_invalid_if_offset_is_less_than_0()
    {
        $data = [
            'status' => 1,
            'limit' => 0,
            'offset' => -1
        ];

        $this->validate($data)->shouldBe(false);

        $this->getErrors()->shouldContainValueLike(new ValidationError(
            "offset",
            "The 'offset' parameter must be provided and have a minimum value of '0' (zero)"
        ));
    }
}
