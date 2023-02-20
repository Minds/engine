<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Monetization\Demonetization\Validators;

use Minds\Core\Config\Config;
use Minds\Core\Settings\Manager as UserSettingsManager;
use Minds\Core\Monetization\Demonetization\Validators\DemonetizedPlusValidator;
use Minds\Core\Settings\Models\UserSettings;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class DemonetizedPlusValidatorSpec extends ObjectBehavior
{
    private Collaborator $config;
    private Collaborator $userSettingsManager;

    public function let(
        Config $config,
        UserSettingsManager $userSettingsManager
    ) {
        $this->beConstructedWith(
            $config,
            $userSettingsManager
        );
        $this->config = $config;
        $this->userSettingsManager = $userSettingsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DemonetizedPlusValidator::class);
    }

    public function it_should_validate_a_urn_that_is_not_plus(User $user)
    {
        $plusUrn = 'urn:plus-tier:123';

        $this->config->get('plus')
            ->shouldBeCalled()
            ->willReturn(['support_tier_urn' => $plusUrn]);

        $this->validateUrn('other urn', $user)->shouldBe(true);
    }

    public function it_should_validate_a_urn_that_is_plus_when_user_is_NOT_demonetized(
        User $user,
        UserSettings $settings
    ) {
        $plusUrn = 'urn:plus-tier:123';

        $this->config->get('plus')
            ->shouldBeCalled()
            ->willReturn(['support_tier_urn' => $plusUrn]);

        $settings->isPlusDemonetized()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->userSettingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userSettingsManager);

        $this->userSettingsManager->getUserSettings()
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->validateUrn($plusUrn, $user)->shouldBe(true);
    }

    public function it_should_not_validate_a_urn_that_is_plus_when_user_IS_demonetized(
        User $user,
        UserSettings $settings
    ) {
        $plusUrn = 'urn:plus-tier:123';

        $this->config->get('plus')
            ->shouldBeCalled()
            ->willReturn(['support_tier_urn' => $plusUrn]);

        $settings->isPlusDemonetized()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->userSettingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userSettingsManager);

        $this->userSettingsManager->getUserSettings()
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->shouldThrow(UserErrorException::class)->during('validateUrn', [$plusUrn, $user]);
    }
}
