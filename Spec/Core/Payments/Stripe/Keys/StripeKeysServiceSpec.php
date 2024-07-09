<?php

namespace Spec\Minds\Core\Payments\Stripe\Keys;

use Minds\Core\Payments\Stripe\Keys\StripeKeysRepository;
use Minds\Core\Payments\Stripe\Keys\StripeKeysService;
use Minds\Core\Payments\Stripe\Webhooks\Services\SubscriptionsWebhookService;
use Minds\Core\Security\Vault\VaultTransitService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class StripeKeysServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $vaultTransitServiceMock;
    private Collaborator $subscriptionsWebhookServiceMock;

    public function let(
        StripeKeysRepository $repositoryMock,
        VaultTransitService $vaultTransitServiceMock,
        SubscriptionsWebhookService $subscriptionsWebhookServiceMock
    ): void {
        $this->beConstructedWith($repositoryMock, $vaultTransitServiceMock, $subscriptionsWebhookServiceMock);
        $this->repositoryMock = $repositoryMock;
        $this->vaultTransitServiceMock = $vaultTransitServiceMock;
        $this->subscriptionsWebhookServiceMock = $subscriptionsWebhookServiceMock;
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

    public function it_should_not_try_to_decrypt_sec_key_if_not_found()
    {
        $this->repositoryMock->getKeys()->willReturn(null);

        $this->vaultTransitServiceMock->decrypt(Argument::any())
            ->shouldNotBeCalled();

        $this->getSecKey()->shouldBe(null);
    }

    public function it_should_set_keys_and_encrypt()
    {
        $this->vaultTransitServiceMock->encrypt('sec-key-plain-text')
            ->willReturn('vault:v1:OkohMC2SSzjw4hSDsV0V0/oxyl0tytgPz5119cEcg4TjaVGRNMlyUYgE2vxNs7SDH1d+a6wET/v+Ih5IqbY3BeCj3BgHA+A3TbEdai+i3QVDZZsJeYb6zwUEr/LDWyMrmMCHz+mYjHWKHlzrHIPrlEewPYElOFh8lis+1dLhvs7f3KDVwrMY');

        $this->repositoryMock->beginTransaction()
            ->shouldBeCalled();
    
        $this->repositoryMock->setKeys('pub-key', 'vault:v1:OkohMC2SSzjw4hSDsV0V0/oxyl0tytgPz5119cEcg4TjaVGRNMlyUYgE2vxNs7SDH1d+a6wET/v+Ih5IqbY3BeCj3BgHA+A3TbEdai+i3QVDZZsJeYb6zwUEr/LDWyMrmMCHz+mYjHWKHlzrHIPrlEewPYElOFh8lis+1dLhvs7f3KDVwrMY')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->subscriptionsWebhookServiceMock->createSubscriptionsWebhook()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repositoryMock->commitTransaction()
            ->shouldBeCalled();
        
        $this->setKeys('pub-key', 'sec-key-plain-text', false)->shouldBe(true);
    }

    public function it_should_get_all_keys(): void
    {
        $keys = [[
            'tenant_id' => 'tenant-id-1',
            'pub_key' => 'pub-key-1',
        ], [
            'tenant_id' => 'tenant-id-2',
            'pub_key' => 'pub-key-2',
        ]];

        $this->repositoryMock->getAllKeys()->willReturn($keys);
        $this->getAllKeys()->shouldBe($keys);
    }
}
