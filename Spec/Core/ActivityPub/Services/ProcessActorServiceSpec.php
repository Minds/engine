<?php

namespace Spec\Minds\Core\ActivityPub\Services;

use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Services\ProcessActorService;
use Minds\Core\ActivityPub\Types\Actor\PersonType;
use Minds\Core\Authentication\Services\RegisterService;
use Minds\Core\Channels\AvatarService;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Security\ACL;
use Minds\Core\Webfinger\WebfingerService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ProcessActorServiceSpec extends ObjectBehavior
{
    private Collaborator $managerMock;
    private Collaborator $actorFactoryMock;
    private Collaborator $aclMock;
    private Collaborator $saveMock;
    private Collaborator $avatarServiceMock;
    private Collaborator $registerServiceMock;
    private Collaborator $webfingerServiceMock;

    public function let(
        Manager $managerMock,
        ActorFactory $actorFactoryMock,
        ACL $aclMock,
        Save $saveMock,
        AvatarService $avatarServiceMock,
        RegisterService $registerServiceMock,
        WebfingerService $webfingerServiceMock,
    ) {
        $this->beConstructedWith($managerMock, $actorFactoryMock, $aclMock, $saveMock, $avatarServiceMock, $registerServiceMock, $webfingerServiceMock);

        $this->managerMock = $managerMock;
        $this->actorFactoryMock = $actorFactoryMock;
        $this->aclMock = $aclMock;
        $this->saveMock = $saveMock;
        $this->avatarServiceMock = $avatarServiceMock;
        $this->registerServiceMock = $registerServiceMock;
        $this->webfingerServiceMock = $webfingerServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ProcessActorService::class);
    }

    public function it_should_resolve_a_webfinger()
    {
        $actor = new PersonType();
        $actor->setId('https://www.minds.com/api/activitypub/user/123');
        $actor->preferredUsername = 'mark';

        // Resolve webfinger for requested username
        $this->actorFactoryMock->fromWebfinger('mark@minds.com')
            ->willReturn($actor);

        // Say we don't have this user already
        $this->managerMock->getEntityFromUri('https://www.minds.com/api/activitypub/user/123')
            ->willReturn(null);

        // Call webfinger from computer document
        $this->webfingerServiceMock->get('acct:mark@www.minds.com')
            ->willReturn([
                'subject' => 'acct:mark@minds.com',
            ]);

        // Another request is made to webfinger to check the uri matches
        $this->managerMock->getUriFromUsername('mark@minds.com', true)
        ->shouldBeCalled()
        ->willReturn('https://www.minds.com/api/activitypub/user/123');

        // It should try and register the user
        $this->registerServiceMock->register(
            'mark@minds.com',
            Argument::type('string'),
            'mark@minds.com',
            Argument::type('string'),
            false,
            true,
            'https://www.minds.com/api/activitypub/user/123'
        )
        ->shouldBeCalled()
        ->willReturn(new User());

        // It will try and update the user
        $this->saveMock->setEntity(Argument::type(User::class))->willReturn($this->saveMock);
        $this->saveMock->save()->willReturn(true);

        // It should try and add the actor
        $this->managerMock->addActor($actor, Argument::type(User::class))->willReturn(true);

        $this->withUsername('mark@minds.com')
            ->process();
    }

    public function it_should_not_resolve_a_forged_webfinger()
    {
        $actor = new PersonType();
        $actor->setId('https://www.minds.com/api/activitypub/user/123');
        $actor->preferredUsername = 'mark';

        // Resolve webfinger for requested username
        $this->actorFactoryMock->fromWebfinger('mark@hacker.com')
            ->willReturn($actor);

        // Say we don't have this user already
        $this->managerMock->getEntityFromUri('https://www.minds.com/api/activitypub/user/123')
            ->willReturn(null);

        // Call webfinger from computer document
        $this->webfingerServiceMock->get('acct:mark@www.minds.com')
            ->willReturn([
                'subject' => 'acct:mark@www.minds.com',
            ]);

        // Another request is made to webfinger to check the uri matches
        $this->managerMock->getUriFromUsername('mark@www.minds.com', true)
        ->shouldNotBeCalled();

        // It should try and register the user
        $this->registerServiceMock->register(
            'mark@www.minds.com',
            Argument::type('string'),
            'mark@www.minds.com',
            Argument::type('string'),
            false,
            true,
            'https://www.minds.com/api/activitypub/user/123',
        )
        ->shouldBeCalled()
        ->willReturn(new User());

        // It will try and update the user
        $this->saveMock->setEntity(Argument::type(User::class))->willReturn($this->saveMock);
        $this->saveMock->save()->willReturn(true);

        // It should try and add the actor
        $this->managerMock->addActor($actor, Argument::type(User::class))->willReturn(true);

        $this->withUsername('mark@hacker.com')
            ->process();
    }
}
