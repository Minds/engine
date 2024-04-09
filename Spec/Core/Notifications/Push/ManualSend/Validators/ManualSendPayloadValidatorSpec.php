<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Notifications\Push\ManualSend\Validators;

use Minds\Core\Notifications\Push\ManualSend\Validators\ManualSendPayloadValidator;
use PhpSpec\ObjectBehavior;

class ManualSendPayloadValidatorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ManualSendPayloadValidator::class);
    }

    public function it_should_validate_a_valid_payload(): void
    {
        $this->validate([
            'platform' => 'ios',
            'token' => 'token'
        ])->shouldBe(true);
    }

    public function it_should_not_validate_a_payload_missing_the_platform(): void
    {
        $this->validate([
            'token' => 'token'
        ])->shouldBe(false);
    }

    public function it_should_not_validate_a_payload_missing_the_token(): void
    {
        $this->validate([
            'platform' => 'ios'
        ])->shouldBe(false);
    }
}
