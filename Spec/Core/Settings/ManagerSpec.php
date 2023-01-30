<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Settings;

use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
use Minds\Core\Settings\Manager;
use Minds\Core\Settings\Models\UserSettings;
use Minds\Core\Settings\Repository;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repository;

    public function let(
        Repository $repository
    ): void {
        $this->repository = $repository;

        $this->beConstructedWith($this->repository);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Manager::class);
    }

    /**
     * @param User $user
     * @return void
     * @throws UserSettingsNotFoundException
     * @throws ServerErrorException
     */
    public function it_should_successfully_get_user_settings(
        User $user,
        UserSettings $settings
    ): void {
        $user->guid = '123';
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user)
            ->willReturn($user);

        $settings->getUserGuid()
            ->willReturn('123');

        $settings->withUser($user)
            ->willReturn($settings);

        $this->repository->getUserSettings(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($settings);

        $this->getUserSettings()
            ->shouldBeEqualTo($settings);
    }

    /**
     * @param User $user
     * @return void
     * @throws UserSettingsNotFoundException
     * @throws ServerErrorException
     */
    public function it_should_throw_user_settings_not_found_exception_when_no_rows_match_in_db(
        User $user
    ): void {
        $user->guid = '123';
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user)
            ->willReturn($user);

        $this->repository->getUserSettings(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willThrow(UserSettingsNotFoundException::class);

        $this->shouldThrow(UserSettingsNotFoundException::class)
            ->during('getUserSettings');
    }

    /**
     * @param User $user
     * @return void
     * @throws ServerErrorException
     * @throws UserSettingsNotFoundException
     */
    public function it_should_successfully_store_settings(
        User $user
    ): void {
        $user->guid = '123';
        $user->getGuid()
            ->willReturn('123');

        $this->setUser($user);

        $this->repository
            ->storeUserSettings(Argument::type(UserSettings::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $data = [];

        $this->storeUserSettings($data)
            ->shouldBeEqualTo(true);
    }
}
