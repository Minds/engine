<?php

namespace Spec\Minds\Core\Blockchain\Events;

use Minds\Core\Blockchain\Transactions\Repository;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Manager as BoostManagerV3;
use Minds\Core\Boost\V3\Models\Boost as BoostV3;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Config;
use Minds\Entities\Boost\Network;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class BoostEventSpec extends ObjectBehavior
{
    protected $txRepository;
    protected $boostRepository;
    protected $config;
    protected $boostManagerV3;
    protected Collaborator $entitiesBuilder;
    protected Collaborator $preApprovalManager;

    public function let(
        Repository $txRepository,
        \Minds\Core\Boost\Repository $boostRepository,
        BoostManagerV3 $boostManagerV3,
        PreApprovalManager $preApprovalManager,
        EntitiesBuilder $entitiesBuilder,
        Config $config
    ) {
        $this->beConstructedWith(
            $txRepository,
            $boostRepository,
            $boostManagerV3,
            $preApprovalManager,
            $entitiesBuilder,
            $config
        );

        $this->txRepository = $txRepository;
        $this->boostRepository = $boostRepository;
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

    public function it_should_execute_a_boost_sent_event(Transaction $transaction, Network $boost)
    {
        $this->boostManagerV3->getBoostByGuid(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'handler' => 'newsfeed',
                'guid' => '1234'
            ]);

        $transaction->getTx()
            ->shouldBeCalled()
            ->willReturn('0x123123asdasd');

        $this->boostRepository->getEntity('newsfeed', '1234')
            ->shouldBeCalled()
            ->willReturn($boost);

        $boost->getState()
            ->shouldBeCalled()
            ->willReturn('pending');

        $boost->setState('created')
            ->shouldBeCalled()
            ->willReturn($boost);

        $boost->save()
            ->shouldBeCalled();

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $this->event(
            '0x68170a430a4e2c3743702c7f839f5230244aca61ed306ec622a5f393f9559040',
            ['address' => '0xasd'],
            $transaction
        );
    }

    public function it_should_execute_a_boost_sent_event_but_not_find_the_boost(Transaction $transaction)
    {
        $this->boostManagerV3->getBoostByGuid(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'handler' => 'newsfeed',
                'guid' => '1234'
            ]);

        $transaction->getTx()
            ->shouldBeCalled()
            ->willReturn('0x123123asdasd');

        $this->boostRepository->getEntity('newsfeed', '1234')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldThrow(new \Exception("No boost with hash 0x123123asdasd"))->during(
            'event',
            [
                '0x68170a430a4e2c3743702c7f839f5230244aca61ed306ec622a5f393f9559040',
                ['address' => '0xasd'],
                $transaction
            ]
        );
    }

    public function it_should_execute_a_boost_sent_event_but_boost_has_been_processed_already(
        Transaction $transaction,
        Network $boost
    ) {
        $this->boostManagerV3->getBoostByGuid(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);
    
        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'handler' => 'newsfeed',
                'guid' => '1234'
            ]);

        $transaction->getTx()
            ->shouldBeCalled()
            ->willReturn('0x123123asdasd');

        $this->boostRepository->getEntity('newsfeed', '1234')
            ->shouldBeCalled()
            ->willReturn($boost);

        $boost->getState()
            ->shouldBeCalled()
            ->willReturn('created');

        $this->shouldThrow(new \Exception("Boost with hash 0x123123asdasd already processed. State: created"))->during(
            'event',
            [
                '0x68170a430a4e2c3743702c7f839f5230244aca61ed306ec622a5f393f9559040',
                ['address' => '0xasd'],
                $transaction
            ]
        );
    }

    public function it_shoud_execute_a_boost_fail_event(Transaction $transaction, Network $boost)
    {
        $this->boostManagerV3->getBoostByGuid(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);
    
        $transaction->getContract()
            ->shouldBeCalled()
            ->willReturn('boost');

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'handler' => 'newsfeed',
                'guid' => '1234'
            ]);

        $this->boostRepository->getEntity('newsfeed', '1234')
            ->shouldBeCalled()
            ->willReturn($boost);

        $transaction->getTx()
            ->shouldBeCalled()
            ->willReturn('0x123123asdasd');

        $boost->getState()
            ->shouldBeCalled()
            ->willReturn('pending');

        $transaction->setFailed(true)
            ->shouldBeCalled();

        $this->txRepository->update($transaction, ['failed'])
            ->shouldBeCalled();

        $boost->setState('failed')
            ->shouldBeCalled()
            ->willReturn($boost);

        $boost->save()
            ->shouldBeCalled();

        $this->event('blockchain:fail', ['address' => '0xasd'], $transaction);
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

    public function it_should_execute_a_boost_fail_event_but_boost_isnt_found(Transaction $transaction)
    {
        $this->boostManagerV3->getBoostByGuid(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $transaction->getContract()
            ->shouldBeCalled()
            ->willReturn('boost');

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'handler' => 'newsfeed',
                'guid' => '1234'
            ]);

        $this->boostRepository->getEntity('newsfeed', '1234')
            ->shouldBeCalled()
            ->willReturn(null);

        $transaction->getTx()
            ->shouldBeCalled()
            ->willReturn('0x123123asdasd');

        $this->shouldThrow(new \Exception("No boost with hash 0x123123asdasd"))->during(
            'event',
            ['blockchain:fail', ['address' => '0xasd'], $transaction]
        );
    }

    public function it_should_execute_a_boost_fail_event_but_boost_already_processed(Transaction $transaction, Network $boost)
    {
        $this->boostManagerV3->getBoostByGuid(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $transaction->getContract()
            ->shouldBeCalled()
            ->willReturn('boost');

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'handler' => 'newsfeed',
                'guid' => '1234'
            ]);

        $this->boostRepository->getEntity('newsfeed', '1234')
            ->shouldBeCalled()
            ->willReturn($boost);

        $transaction->getTx()
            ->shouldBeCalled()
            ->willReturn('0x123123asdasd');

        $boost->getState()
            ->shouldBeCalled()
            ->willReturn('created');

        $this->shouldThrow(new \Exception("Boost with hash 0x123123asdasd already processed. State: created"))->during(
            'event',
            ['blockchain:fail', ['address' => '0xasd'], $transaction]
        );
    }

    public function it_should_record_as_failed(
        Network $boost
    ) {
        $this->boostManagerV3->getBoostByGuid(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $boost->getState()
            ->willReturn('pending');

        $boost->getId()
            ->willReturn('boostID');

        $boost->setState('failed')
            ->shouldBeCalled()
            ->willReturn($boost);

        $boost->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->boostRepository->getEntity('newsfeed', '123')
            ->shouldBeCalled()
            ->willReturn($boost);


        $transaction = new Transaction();
        $transaction->setTx('testTX')
            ->setContract('boost')
            ->setFailed(false)
            ->setData([
                'handler' => 'newsfeed',
                'guid' => '123',
            ]);

        $this->txRepository->update($transaction, ['failed'])
            ->shouldBeCalled();

        $this->boostFail(['address' => '0xasd'], $transaction);
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

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $transaction = new Transaction();
        $transaction->setContract('boost')
            ->setData([ 'guid' => $guid ]);

        $this->boostManagerV3->updateStatus($guid, BoostStatus::PENDING)
            ->shouldBeCalled();

        $this->boostSent(['address' => '0xasd'], $transaction);
    }

    public function it_should_record_as_resolved_for_v3_boosts_that_should_be_preapproved(
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

        $this->preApprovalManager->shouldPreApprove($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $transaction = new Transaction();
        $transaction->setContract('boost')
            ->setData([ 'guid' => $guid ]);

        $this->boostManagerV3->updateStatus($guid, BoostStatus::APPROVED)
            ->shouldBeCalled();

        $this->boostSent(['address' => '0xasd'], $transaction);
    }

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
