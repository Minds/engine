<?php

namespace Spec\Minds\Core\Blockchain\SKALE;

use Minds\Core\Blockchain\Services\Web3Services\MindsSKALEWeb3Service;
use Minds\Core\Blockchain\SKALE\Faucet\FaucetLimiter;
use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\SKALE\Manager;
use Minds\Core\Config;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;

class ManagerSpec extends ObjectBehavior
{
    /** @var MindsSKALEWeb3Service */
    protected $skaleWeb3Service;

    /** @var FaucetLimiter */
    protected $faucetLimiter;

    /** @var Config */
    protected $config;

    public function let(
        MindsSKALEWeb3Service $skaleWeb3Service,
        FaucetLimiter $faucetLimiter,
        Config $config
    ) {
        $this->beConstructedWith(
            $skaleWeb3Service,
            $faucetLimiter,
            $config
        );
        $this->skaleWeb3Service = $skaleWeb3Service;
        $this->faucetLimiter = $faucetLimiter;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_pass_request_to_faucet_if_rate_limit_not_imposed(
        User $user,
    ) {
        $requestAddress = '0x123';
        $responseAddress = '0x456';

        $this->faucetLimiter->checkAndIncrement($user, $requestAddress)
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->skaleWeb3Service->requestFromSKETHFaucet($requestAddress)
            ->shouldBeCalled()
            ->willReturn($responseAddress);

        $this->requestFromFaucet($user, $requestAddress)->shouldReturn($responseAddress);
    }

    public function it_should_get_wallet_from_user_if_no_wallet_passed(
        User $user,
    ) {
        $requestAddress = '0x123';
        $responseAddress = '0x456';

        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn($requestAddress);

        $this->faucetLimiter->checkAndIncrement($user, $requestAddress)
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->skaleWeb3Service->requestFromSKETHFaucet($requestAddress)
            ->shouldBeCalled()
            ->willReturn($responseAddress);

        $this->requestFromFaucet($user, null)->shouldReturn($responseAddress);
    }

    public function it_should_not_pass_request_to_faucet_if_rate_limit_set(
        User $user,
    ) {
        $requestAddress = '0x123';
        $this->faucetLimiter->checkAndIncrement($user, $requestAddress)
            ->shouldBeCalled()
            ->willThrow(new ServerErrorException());
    
        $this->skaleWeb3Service->requestFromSKETHFaucet($requestAddress)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(ServerErrorException::class)
            ->during("requestFromFaucet", [$user, $requestAddress]);
    }

    public function it_should_not_pass_request_to_faucet_if_no_address(
        User $user,
    ) {
        $requestAddress = '0x123';
        $responseAddress = '0x456';

        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->faucetLimiter->checkAndIncrement($user, $requestAddress)
            ->shouldNotBeCalled()
            ->willReturn(true);

        $this
            ->shouldThrow(UserErrorException::class)
            ->during("requestFromFaucet", [$user, null]);
    }
}
