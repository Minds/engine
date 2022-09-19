<?php

namespace Spec\Minds\Core\Supermind\Settings\Validators;

use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Core\Supermind\Settings\Validators\SupermindUpdateSettingsRequestValidator;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use PhpSpec\ObjectBehavior;

class SupermindUpdateSettingsRequestValidatorSpec extends ObjectBehavior
{
    /** @var Settings */
    private $defaultSettings;

    public function let(Settings $defaultSettings)
    {
        $this->beConstructedWith($defaultSettings);
        $this->defaultSettings = $defaultSettings;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SupermindUpdateSettingsRequestValidator::class);
    }

    public function it_should_validate_a_valid_change()
    {
        $data = [
            'min_offchain_tokens' => 10,
            'min_cash' => 20
        ];

        $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::CASH)
            ->shouldBeCalled()
            ->willReturn(10);

        $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN)
            ->shouldBeCalled()
            ->willReturn(1);

        $this->validate($data);

        $this->getErrors()->count()->shouldBe(0);
    }

    public function it_should_NOT_validate_a_change_with_too_many_decimal_places()
    {
        $data = [
            'min_offchain_tokens' => 2.001,
            'min_cash' => 20.001
        ];

        $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::CASH)
            ->shouldBeCalled()
            ->willReturn(10);

        $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN)
            ->shouldBeCalled()
            ->willReturn(1);

        $this->validate($data);

        $this->getErrors()->count()->shouldBe(2);
    }

    public function it_should_NOT_validate_a_change_with_token_value_too_low()
    {
        $data = [
            'min_offchain_tokens' => 0.9,
            'min_cash' => 20
        ];

        $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::CASH)
            ->shouldBeCalled()
            ->willReturn(10);

        $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN)
            ->shouldBeCalled()
            ->willReturn(1);

        $this->validate($data);

        $this->getErrors()->count()->shouldBe(1);
    }

    public function it_should_NOT_validate_a_change_with_cash_value_too_low()
    {
        $data = [
            'min_offchain_tokens' => 1,
            'min_cash' => 9
        ];

        $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::CASH)
            ->shouldBeCalled()
            ->willReturn(10);

        $this->defaultSettings->getDefaultMinimumAmount(SupermindRequestPaymentMethod::OFFCHAIN_TOKEN)
            ->shouldBeCalled()
            ->willReturn(1);

        $this->validate($data);

        $this->getErrors()->count()->shouldBe(1);
    }
}
