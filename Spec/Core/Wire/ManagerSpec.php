<?php

namespace Spec\Minds\Core\Wire;

use Minds\Core;
use Minds\Core\Blockchain\Transactions\Manager as BlockchainManager;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Config;
use Minds\Core\Data\cache\Redis;
use Minds\Core\Queue\SQS\Client;
use Minds\Core\Wire\Repository;
use Minds\Core\Wire\Subscriptions\Manager as SubscriptionsManager;
use Minds\Core\Wire\Wire as WireModel;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Core\Wire\Delegates\CacheDelegate */
    protected $cacheDelegate;

    /** @var Repository */
    protected $repo;

    /** @var BlockchainManager */
    protected $txManager;

    /** @var Core\Blockchain\Transactions\Repository */
    protected $txRepo;

    /** @var Config */
    protected $config;

    /** @var Core\Blockchain\Services\Ethereum */
    protected $client;

    /** @var Core\Blockchain\Token */
    protected $token;

    /** @var Core\Blockchain\Wallets\OffChain\Cap */
    protected $cap;

    /** @var Core\Wire\Delegates\Plus */
    protected $plusDelegate;

    /** @var Core\Blockchain\Wallets\OffChain\Transactions */
    protected $offchainTxs;

    /** @var Core\Payments\Stripe\Intents\Manager */
    protected $stripeIntentsManager;

    public function let(
        Repository $repo,
        BlockchainManager $txManager,
        Core\Blockchain\Transactions\Repository $txRepo,
        Config $config,
        Core\Blockchain\Services\Ethereum $client,
        Core\Blockchain\Token $token,
        Core\Blockchain\Wallets\OffChain\Cap $cap,
        Core\Wire\Delegates\Plus $plusDelegate,
        Core\Wire\Delegates\RecurringDelegate $recurringDelegate,
        Core\Wire\Delegates\NotificationDelegate $notificationDelegate,
        Core\Wire\Delegates\CacheDelegate $cacheDelegate,
        Core\Blockchain\Wallets\OffChain\Transactions $offchainTxs,
        Core\Payments\Stripe\Intents\Manager $stripeIntentsManager
    ) {
        $this->beConstructedWith(
            $repo,
            $txManager,
            $txRepo,
            $config,
            $client,
            $token,
            $cap,
            $plusDelegate,
            $recurringDelegate,
            $notificationDelegate,
            $cacheDelegate,
            $offchainTxs,
            $stripeIntentsManager
        );

        $this->cacheDelegate = $cacheDelegate;
        $this->repo = $repo;
        $this->txManager = $txManager;
        $this->txRepo = $txRepo;
        $this->config = $config;
        $this->client = $client;
        $this->token = $token;
        $this->cap = $cap;

        $this->plusDelegate = $plusDelegate;
        $this->offchainTxs = $offchainTxs;
        $this->stripeIntentsManager = $stripeIntentsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Wire\Manager');
    }

    public function it_should_create_an_onchain_wire()
    {
        $this->txManager->add(Argument::that(function ($transaction) {
            $data = $transaction->getData();

            return $transaction->getUserGuid() == 123
                && $transaction->getAmount() == -100001
                && $transaction->getWalletAddress() == '0xSPEC'
                && $transaction->getContract() == 'wire'
                && $transaction->getTx() == '0xTX'
                && $transaction->isCompleted() == false
                && $data['amount'] == 100001
                && $data['receiver_address'] == '0xRECEIVER'
                && $data['receiver_guid'] == 456
                && $data['sender_address'] == '0xSPEC'
                && $data['sender_guid'] == 123
                && $data['entity_guid'] == 456;
        }))
            ->shouldBeCalled();

        $sender = new User();
        $sender->guid = 123;

        $receiver = new User();
        $receiver->guid = 456;
        $receiver->eth_wallet = '0xRECEIVER';

        $payload = [
            'receiver' => '0xRECEIVER',
            'address' => '0xSPEC',
            'txHash' => '0xTX',
            'method' => 'onchain',
        ];

        $this->setSender($sender)
            ->setEntity($receiver)
            ->setPayload($payload)
            ->setAmount(100001)
            ->create()
            ->shouldReturn(true);
    }

    public function it_should_confirm_a_wire_from_the_blockchain()
    {
        $this->txRepo->add(Argument::that(function ($transaction) {
            return $transaction->getUserGuid() == 123
                && $transaction->getWalletAddress() == '0xRECEIVER'
                && $transaction->getAmount() == 100001
                && $transaction->isCompleted();
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        /*$this->queue->setQueue(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->queue);
        $this->queue->send(Argument::any())
            ->shouldBeCalled();*/

        $receiver = new User();
        $receiver->guid = 123;
        $sender = new User();
        $sender->guid = 123;

        $wire = new WireModel();
        $wire->setReceiver($receiver)
            ->setSender($sender)
            ->setEntity($receiver)
            ->setAmount(100001);

        $this->repo->add($wire)
            ->shouldBeCalled()
            ->willReturn(true);

        $transaction = new Transaction();
        $transaction->setUserGuid(123)
            ->setData([
                'amount' => 100001,
                'receiver_address' => '0xRECEIVER',
            ]);

        $this->confirm($wire, $transaction)
            ->shouldReturn(true);
    }

    public function it_should_create_a_creditcard_wire()
    {
        $this->txManager->add(Argument::that(function ($transaction) {
            $data = $transaction->getData();

            return $transaction->getUserGuid() == 123
                && $transaction->getAmount() == -100001
                && $transaction->getWalletAddress() == '0xSPEC'
                && $transaction->getContract() == 'wire'
                && $transaction->getTx() == '0xTX'
                && $transaction->isCompleted() == false
                && $data['amount'] == 100001
                && $data['receiver_address'] == '0xRECEIVER'
                && $data['receiver_guid'] == 456
                && $data['sender_address'] == '0xSPEC'
                && $data['sender_guid'] == 123
                && $data['entity_guid'] == 456;
        }))
            ->shouldBeCalled();

        $sender = new User();
        $sender->guid = 123;

        $receiver = new User();
        $receiver->guid = 456;
        $receiver->eth_wallet = '0xRECEIVER';

        $payload = [
            'receiver' => '0xRECEIVER',
            'address' => '0xSPEC',
            'txHash' => '0xTX',
            'method' => 'onchain',
        ];

        $this->setSender($sender)
            ->setEntity($receiver)
            ->setPayload($payload)
            ->setAmount(100001)
            ->create()
            ->shouldReturn(true);
    }

    public function it_should_award_trial_to_plus_wire()
    {
        $sender = new User();
        $sender->guid = 123;

        $receiver = new User();
        $receiver->guid = 456;
        $receiver->merchant = [
            'id' => 'mock_id'
        ];
        
        $this->config->get('plus')
            ->willReturn([
                'handler' => 456
            ]);

        $payload = [
            'method' => 'usd',
            'paymentMethodId' => 'mockPaymentId',
        ];

        $this->repo->add(Argument::that(function ($wire) {
            return $wire->getTrialDays() === 7;
        }))
            ->shouldBeCalled();

        $this->stripeIntentsManager->add(Argument::any())
            ->shouldNotBeCalled();

        $this->setSender($sender)
            ->setEntity($receiver)
            ->setPayload($payload)
            ->setAmount(100001)
            ->create()
            ->shouldReturn(true);
    }

    public function it_should_NOT_award_trial_to_plus_wire()
    {
        $sender = new User();
        $sender->guid = 123;
        $sender->plus_expires = 1; // Even though in the past, this tell us plus has been given before

        $receiver = new User();
        $receiver->guid = 456;
        $receiver->merchant = [
            'id' => 'mock_id'
        ];
        
        $this->config->get('plus')
            ->willReturn([
                'handler' => 456
            ]);

        $payload = [
            'method' => 'usd',
            'paymentMethodId' => 'mockPaymentId',
        ];

        $this->repo->add(Argument::that(function ($wire) {
            return !$wire->getTrialDays();
        }))
            ->shouldBeCalled();

        $this->stripeIntentsManager->add(Argument::any())
            ->shouldBeCalled();

        $this->setSender($sender)
            ->setEntity($receiver)
            ->setPayload($payload)
            ->setAmount(100001)
            ->create()
            ->shouldReturn(true);
    }
}
