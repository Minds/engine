<?php
/**
 * Stripe service controller
 * DEPRECATED. Use managers
 */

namespace Minds\Core\Payments\Stripe;

use Minds\Core;
use Minds\Core\Analytics\Timestamps;
use Minds\Core\Config\Config;
use Minds\Core\Payments\Customer;
use Minds\Core\Payments\Merchant;
use Minds\Core\Payments\PaymentMethod;
use Minds\Core\Payments\PaymentServiceInterface;
use Minds\Core\Payments\Sale;
use Minds\Core\Payments\Subscriptions\Subscription;
use Minds\Core\Payments\Subscriptions\SubscriptionPaymentServiceInterface;
use Minds\Core\Payments\Transfers\Transfer;
use Stripe as StripeSDK;

class Stripe implements SubscriptionPaymentServiceInterface
{
    private $config;

    public function __construct(Config $config = null)
    {
        $this->config = $config ?? new Config;
        if ($config->payments && isset($config->payments['stripe'])) {
            $this->setConfig($config->payments['stripe']);
        }
    }

    public function setConfig($config)
    {
        $this->config = $config;
        StripeSDK\Stripe::setApiKey($config['api_key']);
        return $this;
    }


    /**
     * Return a client token
     */
    public function getToken()
    {
        return null;
    }

    /**
     * Create the sale
     * @param Sale $sale
     * @return string - the transaction id
     */
    public function setSale(Sale $sale)
    {
        $opts = [
          'amount' => $sale->getAmount(),
          'capture' => $sale->shouldCapture(),
          'currency' => 'usd',
          //'source' => $sale->getNonce() ?: $sale->getCustomer()->getId(),
          'metadata' => [
            'orderId' => $sale->getOrderId(),
            'first_name' => $sale->getCustomerId()
          ],
        ];

        $source = $sale->getSource();
        switch (substr($source, 0, 3)) {
            case "src":
              $opts['source'] = $source;
              break;
            case "tok":
              $opts['source'] = $source;
              break;
            case "car":
              $opts['customer'] = $sale->getCustomer()->getId();
              $opts['card'] = $source;
              break;
            case "cus":
              $opts['customer'] = $source;
              break;
        }

        $extra = [];

        if ($sale->getMerchant()) {
            $user = $sale->getMerchant();
            $extra['stripe_account'] = $user->getMerchant()['id'];

            if ($sale->getFee()) {
                $opts['application_fee'] = $sale->getAmount() * $sale->getFee();
                //  $opts['destination']['amount'] = $sale->getAmount() - ($sale->getFee() * $sale->getAmount());
            }

            if ($opts['customer']) {
                //we need to clone the customer
                $token = StripeSDK\Token::create(
                    [
                    'customer' => $opts['customer']
                  ],
                    [
                    'stripe_account' => $user->getMerchant()['id']
                  ]
                );
                $opts['customer'] = null;
                $opts['card'] = null;
                $opts['source'] = $token->id;
            }
        }

        $result = StripeSDK\Charge::create($opts, $extra);

        if ($result->status == 'succeeded') {
            return $result->id;
        }

        return false;
    }

    /**
     * Charge the sale
     * @param Sale $sale
     * @return bool
     */
    public function chargeSale(Sale $sale)
    {
        try {
            $opts = [];

            if ($sale->getMerchant()) {
                $opts['stripe_account'] = $sale->getMerchant()->getMerchant()['id'];
            }

            $charge = StripeSDK\Charge::retrieve($sale->getId(), $opts);
            $charge->capture();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Void (if non-settled) or refund the sale
     * @param Sale $sale
     * @return boolean
     * @throws \Exception
     */
    public function voidOrRefundSale(Sale $sale)
    {
        try {
            $this->voidSale($sale);
            return true;
        } catch (\Exception $e) {
            try {
                $this->refundSale($sale);
                return true;
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * Void the sale
     * @param Sale $sale
     * @return void
     */
    public function voidSale(Sale $sale)
    {
        $opts = [];

        if ($sale->getMerchant()) {
            $opts['stripe_account'] = $sale->getMerchant()->getMerchant()['id'];
        }

        StripeSDK\Refund::create([
            "charge" => $sale->getId()
        ], $opts);
    }

    /**
     * Refund the sale
     * @param Sale $sale
     * @return void
     */
    public function refundSale(Sale $sale)
    {
        $opts = [];

        if ($sale->getMerchant()) {
            $opts['stripe_account'] = $sale->getMerchant()->getMerchant()['id'];
        }

        StripeSDK\Refund::create([
            "charge" => $sale->getId()
        ], $opts);
    }

    /**
     * Get a list of transactions
     * @param Merchant $merchant - the merchant
     * @param array $options - limit, offset
     * @return array
     */
    public function getSales(Merchant $merchant, array $options = [])
    {
        $results = StripeSDK\Charge::all(
            [
                'limit' => 3
            ],
            [
                'stripe_account' => $merchant->getId()
            ]
        );

        $sales = [];
        foreach ($results->data as $transaction) {
            $sales[] = (new Sale)
                ->setId($transaction->id)
                ->setAmount($transaction->amount / 100)
                ->setStatus($transaction->outcome['seller_message'])
                ->setMerchant($merchant)
                ->setOrderId($transaction->metadata['orderId'])
                ->setCustomerId($transaction->customer['first_name'])
                ->setCreatedAt($transaction->created)
                ->setSettledAt($transaction->updated);
        }
        return $sales;
    }

    public function getGrossVolume($merchant)
    {
        $results = StripeSDK\BalanceTransaction::all(
            [
          //'type' => 'payment'
        ],
            [
          'stripe_account' => $merchant->getId()
        ]
        );

        $total = [
        'net' => 0,
        'gross' => 0
      ];

        foreach ($results->autoPagingIterator() as $balance) {
            if ($balance->type == 'payout') {
                continue; //we don't want to show the payouts in our total balance
            }
            $total['net'] += $balance->net / 100;
            $total['gross'] += $balance->amount / 100;
        }

        return $total;
    }

    public function getTotalPayouts($merchant)
    {
        $results = StripeSDK\BalanceTransaction::all(
            [
          'type' => 'payout'
        ],
            [
          'stripe_account' => $merchant->getId()
        ]
        );

        $total = 0;

        foreach ($results->autoPagingIterator() as $balance) {
            $total += $balance->amount / 100;
        }

        return $total * -1;
    }

    public function getPayouts(Merchant $merchant, array $options = [])
    {
        $options = array_merge([
            'limit' => 50,
            'offset' => ''
        ], $options);

        if ($options['offset']) {
            $params['starting_after'] = $options['offset'];
        }

        $transactions = StripeSDK\Payout::all(
            $options,
            [
            'stripe_account' => $merchant->getId()
          ]
        );

        $results = [];

        foreach ($transactions->autoPagingIterator() as $transaction) {
            $transaction->amount = $transaction->amount;
            $results[] = $transaction;
        }

        return $results;
    }

    /**
     * Get a list of transactions (filtered)
     * @param Merchant $merchant - the merchant
     * @param array $options - limit, offset
     * @return array
     */
    public function getTransactions(Merchant $merchant, array $options = [])
    {
        $options = array_merge([
            'limit' => 50,
            'offset' => '',
            'orderIdPrefix' => ''
        ], $options);

        $hasFilter = (bool) (
            $options['orderIdPrefix']
        );

        $params = [
            'type' => 'payment',
            'limit' => $hasFilter ? 100 : (int) $options['limit']
        ];

        if ($options['offset']) {
            $params['starting_after'] = $options['offset'];
        }

        $transactions = StripeSDK\BalanceTransaction::all($params, [
            'stripe_account' => $merchant->getId()
        ]);
        $charges = $this->getCharges($merchant, $options);

        $results = [];
        foreach ($transactions->autoPagingIterator() as $transaction) {
            if (
                $options['orderIdPrefix'] &&
                strpos("{$options['orderIdPrefix']}-", $transaction->metadata['orderId']) !== 0
            ) {
                continue;
            }

            foreach ($charges as $charge) {
                if ($charge->balance_transaction == $transaction->id) {
                    $transaction->metadata = $charge->metadata;
                    $transaction->refunded = $charge->refunded;
                    $transaction->dispute = $charge->dispute;
                    $transaction->charge = $charge;
                }
            }

            $results[] = $transaction;

            if (count($results) >= $options['limit']) {
                break;
            }
        }

        //we now want to grab the balance transaction to get the actual amount


        return $results;
    }

    /**
     * Get a list of transactions (filtered)
     * @param Merchant $merchant - the merchant
     * @param array $options - limit, offset
     * @return array
     */
    public function getCharges(Merchant $merchant, array $options = [])
    {
        $options = array_merge([
            'limit' => 50,
            'offset' => '',
            'orderIdPrefix' => ''
        ], $options);

        $hasFilter = (bool) (
            $options['orderIdPrefix']
        );

        $params = [
            'limit' => $hasFilter ? 100 : (int) $options['limit']
        ];

        if ($options['offset']) {
            $params['starting_after'] = $options['offset'];
        }

        $charges = StripeSDK\Charge::all($params, [
            'stripe_account' => $merchant->getId()
        ]);

        $results = [];
        foreach ($charges->autoPagingIterator() as $transaction) {
            if (
                $options['orderIdPrefix'] &&
                strpos("{$options['orderIdPrefix']}-", $transaction->metadata['orderId']) !== 0
            ) {
                continue;
            }

            $results[] = $transaction;

            if (count($results) >= $options['limit']) {
                break;
            }
        }

        return $results;
    }

    /**
     * Get a daily breakdown of transactions
     * @param Merchant $merchant - the merchant
     * @param array $options - limit, offset
     * @return array
     */
    public function getDailyBalance(Merchant $merchant, array $options = [])
    {
        $options = array_merge([
            'days' => 1,
            'orderIdPrefix' => ''
        ], $options);

        $timestamps = Timestamps::span($options['days'], 'day');

        $results = [];

        foreach ($timestamps as $ts) {
            $results[date('Y-m-d', $ts)] = [
                'net' => 0.00,
                'gross' => 0.00
            ];
        }

        $transactions = StripeSDK\BalanceTransaction::all([
            'created' => [
                'gte' => reset($timestamps),
                //'lte' => end($timestamps)
            ],
            'limit' => 50
        ], [
            'stripe_account' => $merchant->getId()
        ]);

        foreach ($transactions->autoPagingIterator() as $transaction) {
            if (
                $options['orderIdPrefix'] &&
                strpos("{$options['orderIdPrefix']}-", $transaction->metadata['orderId']) !== 0
            ) {
                continue;
            }

            $key = date('Y-m-d', $transaction->created);

            $results[$key]['net'] += $transaction->net / 100;
            $results[$key]['gross'] += $transaction->amount / 100;
        }

        return $results;
    }

    /**
      * Get a list of transactions
      * @param Merchant $merchant - the merchant
      * @param array $options - limit, offset
      * @return array
      */
    public function getBalance(Merchant $merchant, array $options = [])
    {
        $results = StripeSDK\BalanceTransaction::all(
            [
                'limit' => $options['limit'] ?: 50
            ],
            [
                'stripe_account' => $merchant->getId()
            ]
        );
        return $results;
    }

    /**
      * Get a list of transactions
      * @param Merchant $merchant - the merchant
      * @param array $options - limit, offset
      * @return array
      */
    public function getTotalBalance(Merchant $merchant, array $options = [])
    {
        $results = StripeSDK\Balance::retrieve([
          'stripe_account' => $merchant->getId()
        ]);

        $totals = [];
        foreach ($results->available as $available) {
            if ($available->amount) {
                $totals[$available->currency] += $available->amount / 100;
            }
        }
        foreach ($results->pending as $pending) {
            if ($pending->amount) {
                //$totals[$pending->currency] += $pending->amount / 100;
            }
        }
        return $totals;
    }

    public function verifyMerchant($id, $file)
    {
        // $result = StripeSDK\FileUpload::create(
        //     [
        //     'purpose' => "identity_document",
        //     'file' => fopen($file['tmp_name'], 'r')
        // ],
        //     ['stripe_account' => $id]
        // );

        // $account = StripeSDK\Account::retrieve($id);
        // $account->legal_entity->verification->document = $result->id;
        // $account->save();

        // return $result->id;
    }

    /* Subscriptions */

    public function createCustomer(Customer $customer)
    {
        $opts = [
            'metadata' => [
                'user_guid' => $customer->getUser()->getGuid()
            ]
        ];

        if ($customer->getPaymentToken()) {
            $opts['source'] = $customer->getPaymentToken();
        }

        $result = StripeSDK\Customer::create($opts);

        $customer->setId($result->id, true);
        return $customer;
    }

    public function getCustomer(Customer $customer)
    {
        try {
            $result = StripeSDK\Customer::retrieve($customer->getId());
        } catch (\Exception $e) {
            return false;
        }
        
        $customer->setPaymentMethods($result->sources->data);

        return $customer;
    }

    public function addCardToCustomer(Customer $customer, $token)
    {
        $customer = StripeSDK\Customer::retrieve($customer->getId());
        $customer->sources->create([
          'source' => $token
        ]);
        return $customer;
    }

    public function removeCardFromCustomer(Customer $customer, $token)
    {
        try {
            $customer = StripeSDK\Customer::retrieve($customer->getId());
            $customer->sources->retrieve($token)->delete();
        } catch (\Exception $e) {
            return false;
        }

        return $customer;
    }

    public function deleteCustomer(Customer $customer)
    {
        try {
            $customer = \Stripe\Customer::retrieve($customer->getId());
            $customer->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createPaymentMethod(PaymentMethod $payment_method)
    {
    }

    public function getPlan($id, $merchant)
    {
        try {
            $result = StripeSDK\Plan::retrieve($id, ['stripe_account' => $merchant]);
        } catch (\Exception $e) {
            return false;
        }
        return $result;
    }

    public function createPlan($plan)
    {
        $result = StripeSDK\Plan::create(
            [
                'amount' => $plan->amount,
                'interval' => 'month',
                'name' => $plan->id,
                'currency' => "usd",
                'id' => $plan->id
            ],
            [
                'stripe_account' => $plan->merchantId
            ]
        );
        return $result;
    }

    public function deletePlan($id, $merchant)
    {
        $plan = StripeSDK\Plan::retrieve($id, ['stripe_account' => $merchant]);
        $plan->delete();
    }

    public function createSubscription(Subscription $subscription)
    {
        $customer = new Customer;
        $customer->setUser($subscription->getUser());

        $params = [
            'customer' => $customer->getId(),
            'plan' => $subscription->getPlanId(),
            'quantity' => $subscription->getQuantity(),
            'metadata' => [
              'orderId' => $subscription->getPlanId() . '-subscription-' . time()
            ]
        ];
        $extras = [];

        if ($coupon = $subscription->getCoupon()) {
            $params['coupon'] = $coupon;
        }

        try {
            if ($subscription->getMerchant()) {
                $merchant = $subscription->getMerchant(); //@todo clean this up
                //subscriptions need to clone customers
                $token = StripeSDK\Token::create(
                    [
                    'customer' => $customer->getId()
                  ],
                    [
                    'stripe_account' => $merchant['id']
                  ]
                );

                $customer = StripeSDK\Customer::create(
                    [
                    'source' => $token->id,
                    'metadata' => [
                      'user_guid' =>  $subscription->getUser()->getGuid()
                    ]
                  ],
                    [
                    'stripe_account' => $merchant['id']
                  ]
                );

                $params['customer'] = $customer->id;

                $params['application_fee_percent'] = $subscription->getFee() * 100;
                $extras['stripe_account'] = $merchant['id'];
            }

            $result = StripeSDK\Subscription::create(
                $params,
                $extras
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $result->id;
    }

    public function getSubscription(Subscription $subscription)
    {
        try {
            $result = StripeSDK\Subscription::retrieve(
                $subscription->getId(),
                [
              'stripe_account' => $subscription->getMerchant()['id']
            ]
            );

            $subscription->setAmount(($result->quantity * $result->plan->amount) / 100);
            $subscription->setNextBillingDate($result->current_period_end);

            return $subscription;
        } catch (StripeSDK\Exception\InvalidRequestException $e) {
            return false;
        }
        return false;
    }

    public function cancelSubscription(Subscription $subscription)
    {
        try {
            return StripeSDK\Subscription::retrieve(
                $subscription->getId(),
                [
                'stripe_account' => $subscription->getMerchant()['id']
            ]
            )->cancel();
        } catch (StripeSDK\Exception\InvalidRequestException $e) {
            return false;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage()); // :v
        }
    }

    public function updateSubscription(Subscription $subscription)
    {
    }

    /* Transfers */

    public function transfer(Transfer $transfer)
    {
        try {
            $result = StripeSDK\Transfer::create([
                'amount' => $transfer->getAmount(),
                'currency' => $transfer->getCurrency(),
                'destination' => $transfer->getDestination(),
                'metadata' => $transfer->getSource(),
                'statement_descriptor' => 'MINDS',
                'source_type' => $this->config['source_type']
            ]);

            $transfer->setId($result->id);
        } catch (StripeSDK\Exception\InvalidRequestException $e) {
            // var_dump($e);die;
            return false;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage()); // :v
        }

        return $transfer;
    }

    public function getCurrencyFor($country)
    {
        $countryToCurrency = [
            'AU' => 'AUD',
            'CA' => 'CAD',
            'GB' => 'GBP',
            'HK' => 'HKD',
            'JP' => 'JPY',
            'SG' => 'SGD',
            'US' => 'USD',
            'NZ' => 'NZD',
        ];

        if (!isset($countryToCurrency[$country])) {
            return 'EUR';
        }

        return $countryToCurrency[$country];
    }
}
