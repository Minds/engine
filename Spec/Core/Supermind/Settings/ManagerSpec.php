<?php

namespace Spec\Minds\Core\Supermind\Settings;

use Minds\Core\Supermind\Settings\Exceptions\SettingsNotFoundException;
use Minds\Core\Supermind\Settings\Manager;
use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Core\Supermind\Settings\Repositories\RepositoryInterface;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var RepositoryInterface */
    private $settingsRepository;

    public function let(RepositoryInterface $settingsRepository)
    {
        $this->beConstructedWith($settingsRepository);
        $this->settingsRepository = $settingsRepository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_settings_from_repository(
        User $user,
        Settings $settings
    ) {
        $this->setUser($user);
        
        $this->settingsRepository->get($user)
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->getSettings()->shouldBe($settings);
    }

    public function it_should_update_settings(
        User $user,
        Settings $settings
    ) {
        $settingsArray = [
            'min_offchain_tokens' => 2,
            'min_cash' => 20
        ];
        
        $this->setUser($user);
        
        $this->settingsRepository->get($user)
            ->shouldBeCalled()
            ->willReturn($settings);

        $settings->setMinOffchainTokens(2)
            ->shouldBeCalled();

        $settings->setMinCash(20)
            ->shouldBeCalled();

        $this->settingsRepository->update($user, $settings)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->updateSettings($settingsArray)->shouldBe(true);
    }

    public function it_should_update_settings_only_if_provided(
        User $user,
        Settings $settings
    ) {
        $settingsArray = [
            'min_offchain_tokens' => 2
        ];
        
        $this->setUser($user);
        
        $this->settingsRepository->get($user)
            ->shouldBeCalled()
            ->willReturn($settings);

        $settings->setMinOffchainTokens(2)
            ->shouldBeCalled();

        $settings->setMinCash(Argument::any())
            ->shouldNotBeCalled();

        $this->settingsRepository->update($user, $settings)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->updateSettings($settingsArray)->shouldBe(true);
    }
}
