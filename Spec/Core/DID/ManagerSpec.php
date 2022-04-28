<?php

namespace Spec\Minds\Core\DID;

use Minds\Core\Config\Config;
use Minds\Core\DID\DIDDocument;
use Minds\Core\DID\Manager;
use Minds\Core\DID\Keypairs;
use Minds\Core\DID\Keypairs\DIDKeypair;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_root_id(Config $config)
    {
        $this->beConstructedWith($config);

        $config->get('did')
            ->willReturn([
                'domain' => 'minds.local'
            ]);

        $this->getId()->shouldBe('did:web:minds.local');
    }

    public function it_should_return_user_id(Config $config, User $user)
    {
        $this->beConstructedWith($config);

        $config->get('did')
            ->willReturn([
                'domain' => 'minds.local'
            ]);

        $user->getUsername()
            ->willReturn('phpspec');

        $this->getId($user)->shouldBe('did:web:minds.local:phpspec');
    }

    public function it_should_return_a_root_document(Config $config)
    {
        $this->beConstructedWith($config);

        $config->get('did')
            ->willReturn([
                'domain' => 'minds.local'
            ]);

        $this->getRootDocument()->shouldBeAnInstanceOf(DIDDocument::class);
        $this->getRootDocument()->export()['id']->shouldBe('did:web:minds.local');
    }

    public function it_should_return_a_user_document(Config $config, EntitiesBuilder $entitiesBuilder, User $user)
    {
        $this->beConstructedWith($config, $entitiesBuilder);

        $config->get('did')
            ->willReturn([
                'domain' => 'minds.local'
            ]);

        $entitiesBuilder->getByUserByIndex('phpspec')
            ->willReturn($user);

        $user->getUsername()
            ->willReturn('phpspec');

        $user->getGuid()
            ->willReturn('123');

        $this->getUserDocument('phpspec')->shouldBeAnInstanceOf(DIDDocument::class);

        $this->getUserDocument('phpspec')->export()['id']->shouldBe('did:web:minds.local:phpspec');
    }

    public function it_should_return_a_user_document_with_keypair(
        Config $config,
        EntitiesBuilder $entitiesBuilder,
        User $user,
        Keypairs\Manager $keypairsManager,
    ) {
        $this->beConstructedWith($config, $entitiesBuilder, $keypairsManager);

        $config->get('did')
            ->willReturn([
                'domain' => 'minds.local'
            ]);

        $entitiesBuilder->getByUserByIndex('phpspec')
            ->willReturn($user);

        $user->getUsername()
            ->willReturn('phpspec');

        $user->getGuid()
            ->willReturn('123');

        //

        $keypair = new DIDKeypair();
        $keypairsManager->getKeypair($user)
            ->willReturn($keypair);
        $keypairsManager->getPublicKey($keypair)
            ->willReturn('base64fake');
        $keypairsManager->getMultibase('base64fake')
            ->willReturn('mbase64fake');

        //

        $this->getUserDocument('phpspec')->shouldBeAnInstanceOf(DIDDocument::class);

        $this->getUserDocument('phpspec')->export()['verificationMethod']->shouldHaveCount(1);
        $this->getUserDocument('phpspec')->export()['authentication']->shouldHaveCount(1);
    }
}
