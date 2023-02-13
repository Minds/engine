<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Monetization\Demonetization\Strategies;

use Minds\Core\Monetization\Demonetization\Strategies\DemonetizePlusUserStrategy;
use Minds\Core\Settings\Manager as SettingsManager;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class DemonetizePlusUserStrategySpec extends ObjectBehavior
{
    protected Collaborator $settingsManager;

    public function let(SettingsManager $settingsManager)
    {
        $this->beConstructedWith($settingsManager);
        $this->settingsManager = $settingsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DemonetizePlusUserStrategy::class);
    }

    public function it_should_execute(
        User $user
    ) {
        $this->settingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->settingsManager);

        $this->settingsManager->storeUserSettings(Argument::that(function ($arg) {
            return is_string($arg['plus_demonetized_ts']);
        }))
            ->shouldBeCalled();

        $this->execute($user)->shouldBe(true);
    }

    public function it_should_not_execute_if_entity_is_not_a_user_instance(
        PaywallEntityInterface $entity
    ) {
        $this->shouldThrow(ServerErrorException::class)->during('execute', [$entity]);
    }
}
