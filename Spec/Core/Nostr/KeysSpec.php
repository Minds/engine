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

    public function it_should_return_a_private_key(User $user)
    {
        $this->didKeypairsManager->getSecp256k1PrivateKey($user)
            ->shouldBeCalled()
            ->willReturn('~xpriv~');

        $this->withUser($user)
            ->getSecp256k1PrivateKey($user)
            ->shouldBe('~xpriv~');
    }

    public function it_should_return_a_public_key(User $user, DIDKeypair $DIDKeypair)
    {
        $this->didKeypairsManager->getKeypair($user)
            ->willReturn($DIDKeypair);

        $randomBytes = "74a4bd3ca4f38f94717ca83cb654b674";

        $this->didKeypairsManager->getSecp256k1PrivateKey($user)
            ->shouldBeCalled()
            ->willReturn($randomBytes);

        $pubKey = $this->withUser($user)->getSecp256k1PublicKey();
        $pubKey->shouldBe("735992d3c6f5b2a58161277bf1d19b5ac954f943cc08c91165ffeaf4f1677c53");
    }
}
