<?php
namespace Minds\Core\Monetization\Partners\Delegates;

use Minds\Core\Monetization\Partners\EarningsPayout;
use Minds\Core\Payments\Stripe\Instances\TransferInstance;
use Minds\Core\Payments\Stripe\Instances\ChargeInstance;
use Minds\Core\Payments\Stripe\Intents;
use Minds\Core\Di\Di;
use Minds\Core\Config;

class PayoutsDelegate
{
    /** @var TransferInstance */
    private $transferInstance;

    /** @var ChargeInstance */
    private $chargeInstance;

    /** @var Intents\Manager */
    private $intentsManager;

    /** @var string */
    private $proStripeAccount;

    /** @var Config */
    private $config;

    public function __construct($config = null, $transferInstance = null, $chargeInstance = null, $intentsManager = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->proStripeAccount = $this->config->get('pro')['stripe_account'];
        $this->transferInstance = $transferInstance ?? new TransferInstance();
        $this->chargeInstance = $chargeInstance ?? new ChargeInstance();
        $this->intentsManager = $intentsManager ?? new Intents\Manager();
    }


    public function onUsdPayout(EarningsPayout $earningsPayout): void
    {
        $intent = new Intents\PaymentIntent();
        $intent->setAmount($earningsPayout->getAmountCents())
            ->setConfirm(true)
            ->setOffSession(true)
            ->setStripeAccountId($earningsPayout->getDestinationId())
            //->setPaymentMethod('ba_1GXRIvEtkBDgTlGKvjWXajXk')
            //->setPaymentMethod('bank')
            ->setPaymentMethod('card_1GYrg8EtkBDgTlGKhlw24o0x')
            ->setUserGuid('1030390936930099216')
            ->setCustomerId('cus_H5cDc4UqBJOzuP');

        $this->intentsManager->add($intent);
        
        /*$this->chargeInstance->create([
            'customer' => 'cus_H5cDc4UqBJOzuP',
            'source' => 'card_1GXReqEtkBDgTlGKnXHERQQs',
          //'source' => 'ba_1GXRIvEtkBDgTlGKvjWXajXk',
          'amount' => $earningsPayout->getAmountCents(),
          'currency' => 'usd',
          //'application_fee_amount' => 0,
          'on_behalf_of' => $earningsPayout->getDestinationId(),
      ]);*/

        /*$this->transferInstance->create([
            'amount' => $earningsPayout->getAmountCents(),
            'currency' => 'usd',
            'destination' => $earningsPayout->getDestinationId(),
        ], [
            //'stripe_account' => $this->proStripeAccount,
        ]);*/
    }
}
