<?php

namespace Spec\Minds\Core\Payments\Stripe\Keys;

use Minds\Core\Payments\Stripe\Keys\StripeKeysRepository;
use Minds\Core\Payments\Stripe\Keys\StripeKeysService;
use Minds\Core\Security\Vault\VaultTransitService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class StripeKeysServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $vaultTransitServiceMock;

    public function let(StripeKeysRepository $repositoryMock, VaultTransitService $vaultTransitServiceMock)
    {
        $this->beConstructedWith($repositoryMock, $vaultTransitServiceMock);
        $this->repositoryMock = $repositoryMock;
        $this->vaultTransitServiceMock = $vaultTransitServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StripeKeysService::class);
    }

    public function it_should_get_pub_key()
    {
        $this->repositoryMock->getKeys()->willReturn([
            'pub-key', 'sec-key'
        ]);
        $this->getPubKey()->shouldBe('pub-key');
    }

    public function it_should_get_plaintext_sec_key()
    {
        $this->repositoryMock->getKeys()->willReturn([
            'pub-key', 'sec-key-cipher-text'
        ]);

        $this->vaultTransitServiceMock->decrypt('sec-key-cipher-text')
            ->willReturn('sec-key-plain-text');

        $this->getSecKey()->shouldBe('sec-key-plain-text');
    }

    public function it_should_set_keys_and_encrypt()
    {
        $this->vaultTransitServiceMock->encrypt('sec-key-plain-text')
            ->willReturn('vault:v1:OkohMC2SSzjw4hSDsV0V0/oxyl0tytgPz5119cEcg4TjaVGRNMlyUYgE2vxNs7SDH1d+a6wET/v+Ih5IqbY3BeCj3BgHA+A3TbEdai+i3QVDZZsJeYb6zwUEr/LDWyMrmMCHz+mYjHWKHlzrHIPrlEewPYElOFh8lis+1dLhvs7f3KDVwrMY');
    
        $this->repositoryMock->setKeys('pub-key', 'vault:v1:OkohMC2SSzjw4hSDsV0V0/oxyl0tytgPz5119cEcg4TjaVGRNMlyUYgE2vxNs7SDH1d+a6wET/v+Ih5IqbY3BeCj3BgHA+A3TbEdai+i3QVDZZsJeYb6zwUEr/LDWyMrmMCHz+mYjHWKHlzrHIPrlEewPYElOFh8lis+1dLhvs7f3KDVwrMY')
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->setKeys('pub-key', 'sec-key-plain-text', false)->shouldBe(true);
    }
}
