<?php
namespace Minds\Core\Payments\Stripe\Transactions;

use Minds\Core\Payments\Stripe\Connect\Account;
use Minds\Core\Payments\Stripe\Instances\TransferInstance;
use Minds\Core\Payments\Stripe\Instances\ChargeInstance;
use Minds\Core\Payments\Stripe\Instances\PayoutInstance;
use Minds\Core\Di\Di;
use Minds\Common\Repository\Response;

class Manager
{
    /** @var EntitiesBuilder $entitiesBuilder */
    private $entitiesBuilder;

    /** @var TransferInstance $transferInstance */
    private $transferInstance;

    /** @var ChargeInstance $chargeInstance */
    private $chargeInstance;

    /** @var Account $account */
    private $account;

    public function __construct($entitiesBuilder = null, $transferInstance = null, $chargeInstance = null, $payoutInstance = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->transferInstance = $transferInstance ?? new TransferInstance();
        $this->chargeInstance = $chargeInstance ?? new ChargeInstance();
        $this->payoutInstance = $payoutInstance ?? new PayoutInstance();
    }

    /**
     * Set the account to interface withj
     * @param Account $account
     * @return Manager
     */
    public function setAccount(Account $account): Manager
    {
        $manager = clone $this;
        $manager->account = $account;
        return $manager;
    }

    /**
     * Return transactions from an account object
     * @param Account $account
     * @return Response[Transaction]
     */
    public function getByAccount(Account $account): Response
    {
        $this->account = $account;

        $response = new Response();

        foreach ($this->getTransfers() as $transfer) {
            $response[] = $transfer;
        }

        foreach ($this->getPayouts() as $payout) {
            $response[] = $payout;
        }

        return $response->sort(function ($a, $b) {
            return $a->getTimestamp() < $b->getTimestamp();
        });
    }

    public function getList($opts = []): Response
    {
        $opts = array_merge([
            'from' => strtotime('midnight first day of this month'),
            'to' => time(),
        ], $opts);

        $response = new Response();

        foreach ($this->getTransfers($opts) as $transfer) {
            $response[] = $transfer;
        }

        foreach ($this->getPayouts($opts) as $payout) {
            $response[] = $payout;
        }

        return $response;
    }

    /**
     * Get transfers
     * @param array $opts
     * @return $response
     */
    public function getTransfers(array $opts = []): Response
    {
        $opts = array_merge([
            'from' => 0,
            'to' => time(),
        ], $opts);

        $transfers = $this->transferInstance->all([
            'created' => [
                'gte' => $opts['from'],
                'lt' => $opts['to']
            ],
            'destination' => $this->account->getId()
        ]);

        $response = new Response();
        foreach ($transfers->autoPagingIterator() as $transfer) {
            try {
                $payment = $this->chargeInstance->retrieve($transfer->source_transaction);
            } catch (\Exception $e) {
                continue;
            }
            $transaction = new Transaction();
            $transaction->setId($transfer->id)
                ->setType('wire')
                ->setStatus('paid')
                ->setTimestamp($transfer->created)
                ->setGross($payment->amount)
                ->setFees(0)
                ->setNet($transfer->amount)
                ->setCurrency($transfer->currency)
                ->setCustomerUserGuid($payment->metadata['user_guid'])
                ->setCustomerUser($this->entitiesBuilder->single($payment->metadata['user_guid']));
            $response[] = $transaction;
        }

        return $response;
    }

    /**
     * Return payouts for time period
     * @param array $opts
     * @return Response
     */
    public function getPayouts(array $opts = []): Response
    {
        $opts = array_merge([
            'from' => 0,
            'to' => time(),
        ], $opts);

        $arrivalDate = [
            'gte' => $opts['from'],
            'lt' => $opts['to'],
        ];

        $payouts = $this->payoutInstance->all([
            'arrival_date' => $arrivalDate,
        ], [ 'stripe_account' => $this->account->getId() ]);
        
        $response = new Response();
        foreach ($payouts->autoPagingIterator() as $payout) {
            $transaction = new Transaction();
            $transaction->setId($payout->id)
                ->setType('payout')
                ->setStatus($payout->status)
                ->setTimestamp($payout->arrival_date)
                ->setGross($payout->amount * -1)
                ->setFees(0)
                ->setNet($payout->amount * -1)
                ->setCurrency($payout->currency);
            $response[] = $transaction;
        }

        return $response;
    }
}
