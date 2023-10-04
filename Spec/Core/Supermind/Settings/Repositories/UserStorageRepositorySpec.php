<?php

namespace Spec\Minds\Core\Supermind\Settings\Repositories;

use Minds\Core\Entities\Actions\Save;
use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Core\Supermind\Settings\Repositories\UserStorageRepository;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class UserStorageRepositorySpec extends ObjectBehavior
{
    /** @var Save */
    private $saveAction;

    public function let(Save $saveAction)
    {
        $this->beConstructedWith($saveAction);
        $this->saveAction = $saveAction;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UserStorageRepository::class);
    }

    public function it_should_get_from_user_object(
        User $user,
    ) {
        $user->getSupermindSettings()
            ->shouldBeCalled()
            ->willReturn([
                'min_offchain_tokens' => 1,
                'min_cash' => 10
            ]);

        $this->get($user)->shouldBeLike(new Settings(
            minOffchainTokens: 1,
            minCash: 10
        ));
    }

    public function it_should_update_settings(
        User $user,
        Settings $settings
    ) {
        $rawSettingsArray = [
            'min_cash' => 10,
            'min_offchain_tokens' => 1
        ];

        $settings->jsonSerialize()
            ->shouldBeCalled()
            ->willReturn($rawSettingsArray);

        $user->setSupermindSettings(json_encode($rawSettingsArray))
            ->shouldBeCalled()
            ->willReturn('123');

        $this->saveAction->setEntity($user)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->withMutatedAttributes([
            'supermind_settings'
        ])
            ->shouldBeCalled()
            ->willReturn($this->saveAction);
        
        $this->saveAction->save()
            ->shouldBeCalled();

        $this->update($user, $settings);
    }
}
