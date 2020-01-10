<?php
namespace Minds\Core\Payments\Stripe\Transactions;

use Minds\Core\Payments\Stripe\Connect\Account;
use Minds\Core\Payments\Stripe\Instances\TransferInstance;
use Minds\Core\Payments\Stripe\Instances\ChargeInstance;
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

    public function __construct($entitiesBuilder = null, $transferInstance = null, $chargeInstance = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->transferInstance = $transferInstance ?? new TransferInstance();
        $this->chargeInstance = $chargeInstance ?? new ChargeInstance();
    }

    /**
     * Return transactions from an account object
     * @param Account $account
     * @return Response[Transaction]
     */
    public function getByAccount(Account $account): Response
    {
        $transfers = $this->transferInstance->all([ 'destination' => $account->getId() ]);

        $response = new Response();
        foreach ($transfers->autoPagingIterator() as $transfer) {
            try {
                $payment = $this->chargeInstance->retrieve($transfer->source_transaction);
            } catch (\Exception $e) {
                continue;
            }
            $transaction = new Transaction();
            $transaction->setId($transfer->id)
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
}
