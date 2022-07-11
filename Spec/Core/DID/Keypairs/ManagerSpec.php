<?php

namespace Spec\Minds\Core\DID\Keypairs;

use Minds\Core\DID\Keypairs\DIDKeypair;
use Minds\Core\DID\Keypairs\Manager;
use Minds\Core\DID\Keypairs\Repository;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    private $repository;

    public function let(Repository $repository)
    {
        $this->beConstructedWith($repository);
        $this->repository = $repository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_create_keypair(User $user)
    {
        $user->getGuid()
            ->willReturn('123');

        $this->createKeypair($user)
            ->shouldBeAnInstanceOf(DIDKeypair::class);
    }

    public function it_should_add_keypair(DIDKeypair $DIDKeypair)
    {
        $this->repository->add($DIDKeypair)
            ->willReturn(true);

        $this->add($DIDKeypair)->shouldBe(true);
    }

    public function it_should_get_keypair(User $user, DIDKeypair $DIDKeypair)
    {
        $user->getGuid()
            ->willReturn('123');

        $this->repository->get('123')
            ->willReturn($DIDKeypair);

        $this->getKeypair($user)
            ->shouldBe($DIDKeypair);
    }

    public function it_should_get_pub_key(DIDKeypair $DIDKeypair)
    {
        $keypair = sodium_crypto_sign_keypair();
        $pubKey = sodium_crypto_sign_publickey($keypair);

        $DIDKeypair->getKeypair()->willReturn($keypair);
        
        $this->getPublicKey($DIDKeypair)
            ->shouldBe($pubKey);
    }

    public function it_should_get_priv_key(DIDKeypair $DIDKeypair)
    {
        $keypair = sodium_crypto_sign_keypair();
        $privKey = sodium_crypto_sign_secretkey($keypair);

        $DIDKeypair->getKeypair()->willReturn($keypair);
        
        $this->getPrivateKey($DIDKeypair)
            ->shouldBe($privKey);
    }

    public function it_should_return_base64_multibase()
    {
        $base64 = base64_encode('this is a string');
        $this->getMultibase('this is a string')
            ->shouldBe('m'.$base64);
    }

    public function it_should_return_a_private_key(
        User $user,
        DIDKeypair $DIDKeypair
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('~user_guid~');

        $this->repository->get('~user_guid~')
            ->willReturn($DIDKeypair);

        $keypair = sodium_crypto_sign_keypair();
        $privKey = sodium_crypto_sign_secretkey($keypair);

        $DIDKeypair->getKeypair()
            ->shouldBeCalledTimes(1)
            ->willReturn($keypair);

        $privKey = $this->getSecp256k1PrivateKey($user);
        $privKey->shouldBe(pack("H*", hash('sha256', sodium_crypto_sign_secretkey($keypair))));
    }
}
