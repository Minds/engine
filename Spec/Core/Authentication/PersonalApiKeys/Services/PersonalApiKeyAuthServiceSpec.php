<?php

namespace Spec\Minds\Core\Authentication\PersonalApiKeys\Services;

use DateTimeImmutable;
use Minds\Core\Authentication\PersonalApiKeys\PersonalApiKey;
use Minds\Core\Authentication\PersonalApiKeys\Repositories\PersonalApiKeyRepository;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyAuthService;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyHashingService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class PersonalApiKeyAuthServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $hashingServiceMock;

    public function let(PersonalApiKeyRepository $repositoryMock, PersonalApiKeyHashingService $hashingServiceMock)
    {
        $this->beConstructedWith($repositoryMock, $hashingServiceMock);
        $this->repositoryMock = $repositoryMock;
        $this->hashingServiceMock = $hashingServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PersonalApiKeyAuthService::class);
    }

    public function it_should_return_a_personal_api_key_from_a_secrets()
    {
        $secret = 'pak_f40505a9f8b4b46fe84176f7e9e894ca1ea724f7052e9cc1da769ceb0bd9d063';

        $this->hashingServiceMock->hashSecret($secret)
            ->willReturn('hashed');
        
        $this->repositoryMock->getBySecretHash('hashed')
            ->willReturn(new PersonalApiKey('1', 1, 'hashed', 'name', [], new DateTimeImmutable(), new DateTimeImmutable()));

        $personalApiKey = $this->getKeyBySecret($secret);
        $personalApiKey->shouldBeAnInstanceOf(PersonalApiKey::class);
    }

    public function it_should_accept_valid_key()
    {
        $personalApiKey = new PersonalApiKey(
            id: 'id',
            ownerGuid: 123,
            secretHash: 'hash',
            name: 'name',
            scopes: [],
            timeCreated: new DateTimeImmutable(),
        );

        $this->validateKey($personalApiKey)->shouldBe(true);
    }

    public function it_should_accept_valid_key_with_future_expiry()
    {
        $personalApiKey = new PersonalApiKey(
            id: 'id',
            ownerGuid: 123,
            secretHash: 'hash',
            name: 'name',
            scopes: [],
            timeCreated: new DateTimeImmutable(),
            timeExpires: new DateTimeImmutable('+1 year'),
        );

        $this->validateKey($personalApiKey)->shouldBe(true);
    }

    public function it_should_accept_valid_key_with_negative_expiry()
    {
        $personalApiKey = new PersonalApiKey(
            id: 'id',
            ownerGuid: 123,
            secretHash: 'hash',
            name: 'name',
            scopes: [],
            timeCreated: new DateTimeImmutable(),
            timeExpires: new DateTimeImmutable('-1 year'),
        );

        $this->validateKey($personalApiKey)->shouldBe(false);
    }
}
