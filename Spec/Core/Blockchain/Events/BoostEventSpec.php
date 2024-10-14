<?php

namespace Spec\Minds\Core\Blockchain\Events;

use Minds\Core\Blockchain\Transactions\Repository;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Manager as BoostManagerV3;
use Minds\Core\Boost\V3\Models\Boost as BoostV3;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class BoostEventSpec extends ObjectBehavior
{
    protected $txRepository;
    protected $config;
    protected $boostManagerV3;
    protected Collaborator $entitiesBuilder;
    protected Collaborator $preApprovalManager;

    public function let(
        Repository $txRepository,
        BoostManagerV3 $boostManagerV3,
        PreApprovalManager $preApprovalManager,
        EntitiesBuilder $entitiesBuilder,
        Config $config
    ) {
        $this->beConstructedWith(
            $txRepository,
            $boostManagerV3,
            $preApprovalManager,
            $entitiesBuilder,
            $config
        );

        $this->txRepository = $txRepository;
        $this->boostManagerV3 = $boostManagerV3;
        $this->preApprovalManager = $preApprovalManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->config = $config;

        $this->config->get('blockchain')
            ->willReturn([
                'contracts' => [
                    'boost' => [
                        'contract_address' => '0xasd'
                    ]
                ]
            ]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Blockchain\Events\BoostEvent');
    }

    public function it_should_get_the_topics()
    {
        $this->getTopics()->shouldReturn([
            '0x68170a430a4e2c3743702c7f839f5230244aca61ed306ec622a5f393f9559040',
            '0xd7ccb5dc8647fd89286a201b04b5e65fb7b5e281603e972695fd35f52bbd244b',
            '0xc43f9053be9f0ee374d3f8eb929d2e0aa990d33a7d4a51423cb715228d39ab89',
            '0x0b869ea800008714ae430dc6c4e12a2c880d50fb92937d51a4b223af34040971',
            'blockchain:fail'
        ]);
    }

    public function it_should_execute_a_boost_fail_event_but_not_a_boost(Transaction $transaction)
    {
        $transaction->getContract()
            ->shouldBeCalled()
            ->willReturn('wire');

        $this->shouldThrow(new \Exception("Failed but not a boost"))->during(
            'event',
            ['blockchain:fail', ['address' => '0xasd'], $transaction]
        );
    }

    public function it_should_record_as_failed_for_v3_boosts(
        BoostV3 $boost
    ) {
        $guid = '1234';
        $this->boostManagerV3->getBoostByGuid($guid)
            ->shouldBeCalled()
            ->willReturn($boost);

        $boost->getStatus()
            ->shouldBeCalled()
            ->willReturn(BoostStatus::PENDING_ONCHAIN_CONFIRMATION);

        $transaction = new Transaction();
        $transaction->setContract('boost')
            ->setData([ 'guid' => $guid ]);

        $this->boostManagerV3->updateStatus($guid, BoostStatus::FAILED)
            ->shouldBeCalled();

        $this->boostFail(['address' => '0xasd'], $transaction);
    }

    public function it_should_record_as_resolved_for_v3_boosts_that_should_not_be_preapproved(
        BoostV3 $boost,
        User $user
    ) {
        $guid = '1234';
        $ownerGuid = '2345';

        $this->boostManagerV3->getBoostByGuid($guid)
            ->shouldBeCalled()
            ->willReturn($boost);

        $boost->getStatus()
            ->shouldBeCalled()
            ->willReturn(BoostStatus::PENDING_ONCHAIN_CONFIRMATION);

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        // $this->preApprovalManager->shouldPreApprove($user)
        //     ->shouldBeCalled()
        //     ->willReturn(false);

        $transaction = new Transaction();
        $transaction->setContract('boost')
            ->setData([ 'guid' => $guid ]);

        $this->boostManagerV3->updateStatus($guid, BoostStatus::PENDING)
            ->shouldBeCalled();

        $this->boostSent(['address' => '0xasd'], $transaction);
    }

    // public function it_should_record_as_resolved_for_v3_boosts_that_should_be_preapproved(
    //     BoostV3 $boost,
    //     User $user
    // ) {
    //     $guid = '1234';
    //     $ownerGuid = '2345';

    //     $this->boostManagerV3->getBoostByGuid($guid)
    //         ->shouldBeCalled()
    //         ->willReturn($boost);

    //     $boost->getStatus()
    //         ->shouldBeCalled()
    //         ->willReturn(BoostStatus::PENDING_ONCHAIN_CONFIRMATION);

    //     $boost->getOwnerGuid()
    //         ->shouldBeCalled()
    //         ->willReturn($ownerGuid);

    //     $this->entitiesBuilder->single($ownerGuid)
    //         ->shouldBeCalled()
    //         ->willReturn($user);

    //     $this->preApprovalManager->shouldPreApprove($user)
    //         ->shouldBeCalled()
    //         ->willReturn(true);

    //     $transaction = new Transaction();
    //     $transaction->setContract('boost')
    //         ->setData([ 'guid' => $guid ]);

    //     $this->boostManagerV3->approveBoost($guid)
    //         ->shouldBeCalled();

    //     $this->boostSent(['address' => '0xasd'], $transaction);
    // }

    public function it_should_fail_if_address_is_wrong(Transaction $transaction)
    {
        $log = [
            'address' => '0xaaa',
            'data' => [
                '0xs123',
                '0xr123',
                '0x123123'
            ]
        ];
        $this->shouldThrow(new \Exception('Event does not match address'))->during(
            'event',
            ['0x68170a430a4e2c3743702c7f839f5230244aca61ed306ec622a5f393f9559040', $log, $transaction]
        );
    }
}
