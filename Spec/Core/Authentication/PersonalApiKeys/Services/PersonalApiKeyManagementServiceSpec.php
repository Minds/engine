<?php

namespace Spec\Minds\Core\Authentication\PersonalApiKeys\Services;

use Minds\Core\Authentication\PersonalApiKeys\PersonalApiKey;
use Minds\Core\Authentication\PersonalApiKeys\Repositories\PersonalApiKeyRepository;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyHashingService;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyManagementService;
use Minds\Core\Guid;
use Minds\Core\Router\Enums\ApiScopeEnum;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class PersonalApiKeyManagementServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $hashingServiceMock;

    public function let(
        PersonalApiKeyRepository $repositoryMock,
        PersonalApiKeyHashingService $hashingServiceMock,
    ) {
        $this->beConstructedWith($repositoryMock, $hashingServiceMock);
        $this->repositoryMock = $repositoryMock;
        $this->hashingServiceMock = $hashingServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PersonalApiKeyManagementService::class);
    }

    public function it_should_return_a_list_of_keys()
    {
        $user = new User();
        $user->guid = Guid::build();

        $this->repositoryMock->getList($user->guid)
            ->shouldBeCalled()
            ->willReturn([null, null]);

        $list = $this->getList($user);
        $list->shouldHaveCount(2);
    }

    public function it_should_create_a_key()
    {
        $user = new User();
        $user->guid = Guid::build();

        $secret = 'pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063';

        $this->hashingServiceMock->generateSecret()
            ->shouldBeCalledOnce()
            ->willReturn($secret);

        $this->hashingServiceMock->hashSecret($secret)
            ->shouldBeCalledOnce()
            ->willReturn('hashed');

        $this->repositoryMock->add(Argument::that(
            fn ($personalApiKey) =>
            $personalApiKey->ownerGuid === (int) $user->guid
            && $personalApiKey->secretHash === 'hashed'
            && $personalApiKey->name === 'test key'
            && $personalApiKey->scopes === [ ApiScopeEnum::SITE_MEMBERSHIP_WRITE ]
            && $personalApiKey->timeExpires === null
        ))
            ->shouldBeCalled()
            ->willReturn(true);

        $key = $this->create($user, 'test key', [ ApiScopeEnum::SITE_MEMBERSHIP_WRITE ]);
        $key->secret->shouldBe($secret);
    }

    public function it_should_create_a_key_with_expiry()
    {
        $user = new User();
        $user->guid = Guid::build();

        $secret = 'pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063';

        $this->hashingServiceMock->generateSecret()
            ->shouldBeCalledOnce()
            ->willReturn($secret);

        $this->hashingServiceMock->hashSecret($secret)
            ->shouldBeCalledOnce()
            ->willReturn('hashed');

        $this->repositoryMock->add(Argument::that(
            fn ($personalApiKey) =>
            $personalApiKey->ownerGuid === (int) $user->guid
            && $personalApiKey->secretHash === 'hashed'
            && $personalApiKey->name === 'test key'
            && $personalApiKey->scopes === [ ApiScopeEnum::SITE_MEMBERSHIP_WRITE ]
            && (int) $personalApiKey->timeExpires->getTimestamp() > strtotime('+30 days') - 60, // avoid clock flake
        ))
            ->shouldBeCalled()
            ->willReturn(true);

        $key = $this->create($user, 'test key', [ ApiScopeEnum::SITE_MEMBERSHIP_WRITE ], 30);
        $key->secret->shouldBe($secret);
    }

    public function it_should_return_a_key_by_id(PersonalApiKey $personalApiKey)
    {
        $guid = Guid::build();
        $user = new User;
        $user->guid = $guid;

        $this->repositoryMock->getById('pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063', $guid)
            ->shouldBeCalledOnce()
            ->willReturn($personalApiKey);

        $this->getById('pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063', $user)
            ->shouldBe($personalApiKey);
    }

    public function it_should_delete_a_key_by_id(PersonalApiKey $personalApiKey)
    {
        $guid = Guid::build();
        $user = new User;
        $user->guid = $guid;

        $this->repositoryMock->delete('pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063', $guid)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->deleteById('pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063', $user)
            ->shouldBe(true);
    }
}
