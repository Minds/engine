<?php

namespace Spec\Minds\Core\Nostr;

use Minds\Core\Nostr\Keys;
use Minds\Core\DID;
use Minds\Core\DID\Keypairs\DIDKeypair;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class KeysSpec extends ObjectBehavior
{
    private $didKeypairsManager;

    public function let(DID\Keypairs\Manager $didKeypairsManager)
    {
        $this->beConstructedWith($didKeypairsManager);
        $this->didKeypairsManager = $didKeypairsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Keys::class);
    }

    public function it_should_return_a_private_key(User $user, DIDKeypair $DIDKeypair)
    {
        $this->didKeypairsManager->getKeypair($user)
            ->willReturn($DIDKeypair);

        $randomBytes = openssl_random_pseudo_bytes(256);

        $this->didKeypairsManager->getPrivateKey($DIDKeypair)
            ->willReturn($randomBytes);

        $privKey = $this->withUser($user)->getSecp256k1PrivateKey();
        $privKey->shouldBe(pack("H*", hash('sha256', $randomBytes)));
    }

    public function it_should_return_a_public_key(User $user, DIDKeypair $DIDKeypair)
    {
        $this->didKeypairsManager->getKeypair($user)
            ->willReturn($DIDKeypair);

        $randomBytes = "74a4bd3ca4f38f94717ca83cb654b674f7a64fc24f5ae26661a131d0cd19d5e7";

        $this->didKeypairsManager->getPrivateKey($DIDKeypair)
            ->willReturn($randomBytes);

        $pubKey = $this->withUser($user)->getSecp256k1PublicKey();
        $pubKey->shouldBe("4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715");
    }
}
