<?php

namespace Spec\Minds\Core\Boost\V3\Onchain;

use Minds\Core\Boost\V3\Onchain\OnchainBoostBackgroundJob;
use Minds\Core\Blockchain\Services\Ethereum as EthereumService;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Manager;
use Minds\Core\Boost\V3\Repository;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
use Minds\Core\Config\Config;
use Minds\Core\Util\BigNumber;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OnchainBoostBackgroundJobSpec extends ObjectBehavior
{
    protected $managerMock;
    protected $repositoryMock;
    protected $preApprovalManagerMock;
    protected $ethereumServiceMock;
    protected $entitiesBuilderMock;
    protected $configMock;

    public function let(
        Manager $managerMock,
        Repository $repositoryMock,
        PreApprovalManager $preApprovalManagerMock,
        EthereumService $ethereumServiceMock,
        EntitiesBuilder $entitiesBuilderMock,
        Config $configMock
    ) {
        $this->beConstructedWith($managerMock, $repositoryMock, $preApprovalManagerMock, $ethereumServiceMock, $entitiesBuilderMock, $configMock);
        $this->managerMock = $managerMock;
        $this->repositoryMock = $repositoryMock;
        $this->preApprovalManagerMock = $preApprovalManagerMock;
        $this->ethereumServiceMock = $ethereumServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;

        $configMock->get('blockchain')
            ->willReturn([
                'contracts' => [
                    'boost' => [
                        'contract_address' => '0x0a180bea9ca0fb7c0ed15989a803e72f1f044c79'
                    ]
                ]
            ]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OnchainBoostBackgroundJob::class);
    }

    public function it_should_move_to_pending(Boost $boostMock)
    {
        $this->repositoryMock->getBoosts(Argument::any(), Argument::any(), BoostStatus::PENDING_ONCHAIN_CONFIRMATION)
            ->willReturn([$boostMock]);

        $boostMock->getGuid()
            ->willReturn('1492863142571544582');
        $boostMock->getOwnerGuid()
            ->willReturn('456');
        $boostMock->getPaymentTxId()
            ->willReturn('0xd34cdd1ec82c952bf706dd4264ed6eaba94e645f8ca27aadc051a80ebb31f2cd');
        $boostMock->getCreatedTimestamp()
            ->willReturn(time());

        $this->ethereumServiceMock->request('eth_getTransactionReceipt', [ '0xd34cdd1ec82c952bf706dd4264ed6eaba94e645f8ca27aadc051a80ebb31f2cd' ])
            ->willReturn([
                'status' => '0x1',
                'logs' => [
                    [
                        'topics' => [ OnchainBoostBackgroundJob::BOOST_SENT_TOPIC ],
                        'address' => '0x0a180bea9ca0fb7c0ed15989a803e72f1f044c79',
                        'data' => '00000000000000000000000000000000000000000000000014b7b71e5f401006'
                    ]
                ],
            ]);

        $this->entitiesBuilderMock->single('456')
            ->willReturn(new User());

        $this->managerMock->updateStatus('1492863142571544582', BoostStatus::PENDING)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->preApprovalManagerMock->shouldPreApprove(Argument::any())
            ->willReturn(false);
    
        $this->run();
    }
    
    public function it_should_move_to_failed(Boost $boostMock)
    {
        $this->repositoryMock->getBoosts(Argument::any(), Argument::any(), BoostStatus::PENDING_ONCHAIN_CONFIRMATION)
            ->willReturn([$boostMock]);

        $boostMock->getGuid()
            ->willReturn('123');
        $boostMock->getPaymentTxId()
            ->willReturn('0xd34cdd1ec82c952bf706dd4264ed6eaba94e645f8ca27aadc051a80ebb31f2cd');
        $boostMock->getCreatedTimestamp()
            ->willReturn(time());

        $this->ethereumServiceMock->request('eth_getTransactionReceipt', [ '0xd34cdd1ec82c952bf706dd4264ed6eaba94e645f8ca27aadc051a80ebb31f2cd' ])
            ->shouldBeCalled()
            ->willReturn([
                'status' => '0x1',
                'logs' => [],
            ]);

        $this->managerMock->updateStatus('123', BoostStatus::FAILED)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->run();
    }
}
