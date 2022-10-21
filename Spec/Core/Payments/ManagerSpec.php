<?php

namespace Spec\Minds\Core\Payments;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Models\GetPaymentsOpts;
use Minds\Core\Payments\Models\Payment;
use Minds\Core\Payments\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Exceptions\UserErrorException;
use Stripe\PaymentIntent;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var IntentsManagerV2 */
    protected $intentsManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Logger */
    protected $logger;

    public function let(
        Repository $repository,
        IntentsManagerV2 $intentsManager,
        EntitiesBuilder $entitiesBuilder,
        Logger $logger
    ) {
        $this->repository = $repository;
        $this->intentsManager = $intentsManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->logger = $logger;

        $this->beConstructedWith(
            $repository,
            $intentsManager,
            $entitiesBuilder,
            $logger
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Payments\Manager');
    }

    public function it_should_create()
    {
        $type = 'test';
        $user_guid = 1000;
        $time_created = 10000000;
        $payment_id = 'test:5000';
        $data = [ 'foo' => 'bar' ];

        $this->repository->upsert(
            $type,
            $user_guid,
            $time_created,
            $payment_id,
            $data
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setType($type)
            ->setUserGuid($user_guid)
            ->setTimeCreated($time_created)
            ->setPaymentId($payment_id)
            ->create($data)
            ->shouldReturn($payment_id);
    }

    public function it_should_create_generating_a_payment_id()
    {
        $type = 'test';
        $user_guid = 1000;
        $time_created = 10000000;
        $data = [ 'foo' => 'bar' ];

        $this->repository->upsert(
            $type,
            $user_guid,
            $time_created,
            Argument::containingString('guid:'),
            $data
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setType($type)
            ->setUserGuid($user_guid)
            ->setTimeCreated($time_created)
            ->create($data)
            ->shouldReturn($this->getPaymentId());
    }

    public function it_should_throw_if_no_type_during_create()
    {
        $user_guid = 1000;
        $time_created = 10000000;
        $payment_id = 'test:5000';
        $data = [ 'foo' => 'bar' ];

        $this->repository->upsert(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setUserGuid($user_guid)
            ->setTimeCreated($time_created)
            ->setPaymentId($payment_id)
            ->shouldThrow(new \Exception('Type is required'))
            ->duringCreate($data);
    }

    public function it_should_throw_if_no_user_guid_during_create()
    {
        $type = 'test';
        $time_created = 10000000;
        $payment_id = 'test:5000';
        $data = [ 'foo' => 'bar' ];

        $this->repository->upsert(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setType($type)
            ->setTimeCreated($time_created)
            ->setPaymentId($payment_id)
            ->shouldThrow(new \Exception('User GUID is required'))
            ->duringCreate($data);
    }

    public function it_should_throw_if_no_time_created_during_create()
    {
        $type = 'test';
        $user_guid = 1000;
        $payment_id = 'test:5000';
        $data = [ 'foo' => 'bar' ];

        $this->repository->upsert(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setType($type)
            ->setUserGuid($user_guid)
            ->setPaymentId($payment_id)
            ->shouldThrow(new \Exception('Time created is required'))
            ->duringCreate($data);
    }

    public function it_should_throw_if_upsert_fails_during_create()
    {
        $type = 'test';
        $user_guid = 1000;
        $time_created = 10000000;
        $payment_id = 'test:5000';
        $data = [ 'foo' => 'bar' ];

        $this->repository->upsert(
            $type,
            $user_guid,
            $time_created,
            $payment_id,
            $data
        )
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->setType($type)
            ->setUserGuid($user_guid)
            ->setTimeCreated($time_created)
            ->setPaymentId($payment_id)
            ->shouldThrow(new \Exception('Cannot save payment'))
            ->duringCreate($data);
    }

    public function it_should_update_payment_by_id()
    {
        $payment_id = 'test:5000';
        $payment_row = [
            'type' => 'test',
            'user_guid' => 1000,
            'time_created' => 10000000
        ];
        $data = [ 'foo' => 'bar' ];

        $this->repository->getByPaymentId($payment_id)
            ->shouldBeCalled()
            ->willReturn($payment_row);

        $this->repository->upsert(
            $payment_row['type'],
            $payment_row['user_guid'],
            $payment_row['time_created'],
            $payment_id,
            $data
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setPaymentId($payment_id)
            ->updatePaymentById($data)
            ->shouldReturn($payment_id);
    }

    public function it_should_return_false_if_no_row_during_update_payment_by_id()
    {
        $payment_id = 'test:5000';
        $payment_row = false;
        $data = [ 'foo' => 'bar' ];

        $this->repository->getByPaymentId($payment_id)
            ->shouldBeCalled()
            ->willReturn($payment_row);

        $this->repository->upsert(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setPaymentId($payment_id)
            ->updatePaymentById($data)
            ->shouldReturn(false);
    }

    public function it_should_throw_if_upsert_fails_during_update_payment_by_id()
    {
        $payment_id = 'test:5000';
        $payment_row = [
            'type' => 'test',
            'user_guid' => 1000,
            'time_created' => 10000000
        ];
        $data = [ 'foo' => 'bar' ];

        $this->repository->getByPaymentId($payment_id)
            ->shouldBeCalled()
            ->willReturn($payment_row);

        $this->repository->upsert(
            $payment_row['type'],
            $payment_row['user_guid'],
            $payment_row['time_created'],
            $payment_id,
            $data
        )
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->setPaymentId($payment_id)
            ->shouldThrow(new \Exception('Cannot update payment'))
            ->duringUpdatePaymentById($data);
    }

    public function it_should_get_payments(GetPaymentsOpts $opts)
    {
        $this->setUserGuid('123');

        $this->intentsManager->getPaymentIntentsByUserGuid(
            userGuid: '123',
            opts: $opts
        )
            ->shouldBeCalled()
            ->willReturn([
                'data' => [
                    [
                        'status' => 'succeeded',
                        'id' => 'pay_123',
                        'currency' => 'usd',
                        'amount' => 1000,
                        'statement_descriptor' => 'Minds: Plus sub',
                        'created' => 10000000,
                        'metadata' => [
                            'receiver_guid' => '123'
                        ],
                        'charges' => [
                            'data' => [
                                [
                                    'status' => 'succeeded',
                                    'receipt_url' => 'https://www.minds.com/'
                                ]
                            ]
                        ]
                    ],
                    [
                        'status' => 'succeeded',
                        'id' => 'pay_234',
                        'currency' => 'usd',
                        'amount' => 2000,
                        'statement_descriptor' => 'Minds: Supermind',
                        'created' => 20000000,
                        'metadata' => [
                            'receiver_guid' => '234'
                        ],
                        'charges' => [
                            'data' => [
                                [
                                    'status' => 'succeeded',
                                    'receipt_url' => 'https://www.minds.com/'
                                ]
                            ]
                        ]
                    ],
                ],
                'has_more' => true
            ]);

        $this->getPayments($opts)->shouldBeLike([
            'has_more' => true,
            'data' => [
                (
                    (new Payment())
                        ->setStatus('succeeded')
                        ->setCurrency('usd')
                        ->setStatementDescriptor('Minds: Plus sub')
                        ->setMinorUnitAmount(1000)
                        ->setCreatedTimestamp(10000000)
                        ->setReceiptUrl('https://www.minds.com/')
                        ->setPaymentId('pay_123')
                ),
                (
                    (new Payment())
                        ->setStatus('succeeded')
                        ->setCurrency('usd')
                        ->setStatementDescriptor('Minds: Supermind')
                        ->setMinorUnitAmount(2000)
                        ->setCreatedTimestamp(20000000)
                        ->setReceiptUrl('https://www.minds.com/')
                        ->setPaymentId('pay_234')
                )
            ]
        ]);
    }

    public function it_should_throw_an_error_if_no_payments_are_found(GetPaymentsOpts $opts)
    {
        $this->setUserGuid('123');

        $this->intentsManager->getPaymentIntentsByUserGuid(
            userGuid: '123',
            opts: $opts
        )
            ->shouldBeCalled()
            ->willReturn([ 'data' => [] ]);

        $this->shouldThrow(UserErrorException::class)->during('getPayments', [$opts]);
    }

    public function it_should_select_primary_charge_as_successful_charge(GetPaymentsOpts $opts)
    {
        $this->setUserGuid('123');

        $this->intentsManager->getPaymentIntentsByUserGuid(
            userGuid: '123',
            opts: $opts
        )
            ->shouldBeCalled()
            ->willReturn([
                'data' => [
                    [
                        'status' => 'succeeded',
                        'id' => 'pay_234',
                        'currency' => 'usd',
                        'amount' => 2000,
                        'statement_descriptor' => 'Minds: Supermind',
                        'created' => 20000000,
                        'metadata' => [
                            'receiver_guid' => '234'
                        ],
                        'charges' => [
                            'data' => [
                                [
                                    'status' => 'failed',
                                    'receipt_url' => 'https://www.minds.com/'
                                ],
                                [
                                    'status' => 'succeeded',
                                    'receipt_url' => 'succeeded_receipt_url'
                                ],
                                [
                                    'status' => 'failed',
                                    'receipt_url' => 'https://www.minds.com/'
                                ]
                            ]
                        ]
                    ]
                ],
                'has_more' => true
            ]);

        $this->getPayments($opts)->shouldBeLike([
            'has_more' => true,
            'data' => [
                (
                    (new Payment())
                        ->setStatus('succeeded')
                        ->setCurrency('usd')
                        ->setStatementDescriptor('Minds: Supermind')
                        ->setMinorUnitAmount(2000)
                        ->setCreatedTimestamp(20000000)
                        ->setReceiptUrl('succeeded_receipt_url')
                        ->setPaymentId('pay_234')
                )
            ]
        ]);
    }

    public function it_should_select_primary_charge_as_last_unsuccessful_charge_when_no_successful_charge(
        GetPaymentsOpts $opts
    ) {
        $this->setUserGuid('123');

        $this->entitiesBuilder->single('234')
            ->shouldBeCalled();

        $this->intentsManager->getPaymentIntentsByUserGuid(
            userGuid: '123',
            opts: $opts
        )
            ->shouldBeCalled()
            ->willReturn([
                'data' => [
                    [
                        'status' => 'succeeded',
                        'id' => 'pay_234',
                        'currency' => 'usd',
                        'amount' => 2000,
                        'statement_descriptor' => 'Minds: Supermind',
                        'created' => 20000000,
                        'metadata' => [
                            'receiver_guid' => '234'
                        ],
                        'charges' => [
                            'data' => [
                                [
                                    'status' => 'failed',
                                    'receipt_url' => 'https://www.minds.com/'
                                ],
                                [
                                    'status' => 'failed',
                                    'receipt_url' => 'https://www.minds.com/'
                                ],
                                [
                                    'status' => 'failed',
                                    'receipt_url' => 'last_failure'
                                ]
                            ]
                        ]
                    ]
                ],
                'has_more' => true
            ]);

        $this->getPayments($opts)->shouldBeLike([
            'has_more' => true,
            'data' => [
                (
                    (new Payment())
                        ->setStatus('succeeded')
                        ->setCurrency('usd')
                        ->setStatementDescriptor('Minds: Supermind')
                        ->setMinorUnitAmount(2000)
                        ->setCreatedTimestamp(20000000)
                        ->setReceiptUrl('last_failure')
                        ->setPaymentId('pay_234')
                )
            ]
        ]);
    }

    public function it_should_get_a_payment_by_id()
    {
        $paymentId = 'pay_123';

        $this->entitiesBuilder->single('234')
            ->shouldBeCalled();

        $this->entitiesBuilder->single('123')
            ->shouldBeCalled();

        $this->intentsManager->getPaymentIntentByPaymentId($paymentId)
            ->shouldBeCalled()
            ->willReturn([
                'status' => 'succeeded',
                'id' => 'pay_123',
                'currency' => 'usd',
                'amount' => 2000,
                'statement_descriptor' => 'Minds: Supermind',
                'created' => 20000000,
                'metadata' => [
                    'receiver_guid' => '234',
                    'user_guid' => '123'
                ],
                'charges' => [
                    'data' => [
                        [
                            'status' => 'success',
                            'receipt_url' => 'https://www.minds.com/'
                        ]
                    ]
                ]
            ]);

        $this->getPaymentById($paymentId)->shouldBeLike(
            (new Payment())
                ->setStatus('succeeded')
                ->setCurrency('usd')
                ->setStatementDescriptor('Minds: Supermind')
                ->setMinorUnitAmount(2000)
                ->setCreatedTimestamp(20000000)
                ->setReceiptUrl('https://www.minds.com/')
                ->setPaymentId('pay_123')
        );
    }

    public function it_should_pass_through_exceptions_thrown_getting_payment_by_id()
    {
        $paymentId = 'pay_123';

        $this->intentsManager->getPaymentIntentByPaymentId($paymentId)
            ->shouldBeCalled()
            ->willThrow(new \Exception());

        $this->shouldThrow(\Exception::class)->during('getPaymentById', [$paymentId]);
    }
}
