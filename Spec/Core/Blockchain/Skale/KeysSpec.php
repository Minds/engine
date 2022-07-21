<?php

namespace Spec\Minds\Core\Blockchain\Skale;

use Minds\Core\Blockchain\Skale\Keys;
use PhpSpec\ObjectBehavior;
use Minds\Entities\User;
use Minds\Core\DID\Keypairs\Manager as DidKeypairsManager;

class KeysSpec extends ObjectBehavior
{
    /** @var DidKeypairsManager */
    private $didKeypairsManager;

    public function let(
        DidKeypairsManager $didKeypairsManager
    ) {
        $this->didKeypairsManager = $didKeypairsManager;
        
        $this->beConstructedWith($didKeypairsManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Keys::class);
    }

    public function it_should_construct_a_new_instance_with_a_given_user(
        User $user
    ) {
        $this->withUser($user)->shouldHaveType(\Minds\Core\Blockchain\Skale\Keys::class);
    }
    
    public function it_should_get_private_key(User $user)
    {
        $xpriv = '~xpriv~';

        $this->didKeypairsManager->getSecp256k1PrivateKey($user)
            ->shouldBeCalled()
            ->willReturn($xpriv);

        $instance = $this->withUser($user);
        
        $instance->getSecp256k1PrivateKey()->shouldBe($xpriv);
    }

    public function it_should_get_private_key_as_hex(User $user)
    {
        $xpriv = '~xpriv~';

        $this->didKeypairsManager->getSecp256k1PrivateKey($user)
            ->shouldBeCalled()
            ->willReturn($xpriv);

        $instance = $this->withUser($user);
        
        $instance->getSecp256k1PrivateKeyAsHex()->shouldBe('0x7e78707269767e');
    }
}
