<?php

namespace Minds\Core\Payments\Stripe\Connect;

use Minds\Core\Entities\Actions\Save;
use Minds\Core\Payments\Stripe\Connect\Delegates\NotificationDelegate;
use Minds\Core\Payments\Stripe\Currencies;
use Minds\Core\Payments\Stripe\Instances\AccountInstance;
use Minds\Core\Payments\Stripe\Instances\BalanceInstance;
use Minds\Core\Payments\Stripe\Instances\FileInstance;
use Minds\Core\Payments\Stripe\Transactions;
use Stripe;
use Minds\Entities\User;

class Manager
{
    /** @var Save $save */
    private $save;

    /** @var NotificationDelegate */
    private $notificationDelegate;

    /** @var AccountInstance $accountInstance */
    private $accountInstance;

    /** @var BalanceInstance $balanceInstance */
    private $balanceInstance;

    /** @var FileInstance $fileInstance */
    private $fileInstance;
    
    /** @var Transactions\Manager */
    private $transactionsManager;

    public function __construct(
        Save $save = null,
        NotificationDelegate $notificationDelegate = null,
        AccountInstance $accountInstance = null,
        BalanceInstance $balanceInstance = null,
        FileInstance $fileInstance = null,
        Transactions\Manager $transactionsManager = null
    ) {
        $this->save = $save ?: new Save();
        $this->notificationDelegate = $notificationDelegate ?: new NotificationDelegate();
        $this->accountInstance = $accountInstance ?: new AccountInstance();
        $this->balanceInstance = $balanceInstance ?: new BalanceInstance();
        $this->fileInstance = $fileInstance ?: new FileInstance();
        $this->transactionsManager = $transactionsManager ?? new Transactions\Manager();
    }

    /**
     * Add a conenct account to stripe
     * @param Account $account
     * @return Account
     */
    public function add(Account $account) : Account
    {
        $dob = explode('-', $account->getDateOfBirth());
        $data = [
          'type' => 'custom',
          'country' => $account->getCountry(),
          'business_type' => 'individual',
          'individual' => [
            'first_name' => $account->getFirstName(),
            'last_name' => $account->getLastName(),
            'address' => [
              'city' => $account->getCity(),
              'line1' => $account->getStreet(),
              'postal_code' => $account->getPostCode(),
              'state' => $account->getState(),
            ],
            'dob' => [
              'day' => $dob[2],
              'month' => $dob[1],
              'year' => $dob[0]
            ],
          ],
          'metadata' => $account->getMetadata(),
          'requested_capabilities' => [ 'card_payments', 'transfers' ],
          'tos_acceptance' => [
            'date' => time(),
            'ip' => $account->getIp(),
          ],
          'settings' => [
            'payouts' => [
              'schedule' => [
                'interval' => null,
                'monthly_anchor' => null,
              ],
            ]
          ]
        ];

        $payoutInterval = $account->getPayoutInterval();

        if ($payoutInterval && $payoutInterval !== 'monthly') {
            $data['settings']['payouts']['schedule']['interval'] = $payoutInterval;
        } else {
            $data['settings']['payouts']['schedule']['interval'] = 'monthly';
            $data['settings']['payouts']['schedule']['monthly_anchor'] = 28;
        }

        // Required for JP only

        if ($account->getGender()) {
            $data['individual']['gender'] = $account->getGender();
        }

        if ($account->getEmail()) {
            $data['individual']['email'] = $account->getEmail();
        }

        if ($account->getUrl()) {
            $data['business_profile']['url'] = $account->getUrl();
        }

        if ($account->getPhoneNumber()) {
            $data['individual']['phone'] = "+" . $account->getPhoneNumber();
        }

        // US 1099 requires SSN

        if ($account->getSSN()) {
            $data['individual']['ssn_last_4'] = $account->getSSN();
            $data['requested_capabilities'] = ['legacy_payments', 'card_payments', 'transfers'];
        }

        if ($account->getPersonalIdNumber()) {
            $data['individual']['id_number'] = $account->getPersonalIdNumber();
        }

        $result = $this->accountInstance->create($data);

        if (!$result->id) {
            throw new \Exception($result->message);
        }

        $account->setId($result->id);

        // Save reference directly to user entity

        $user = $account->getUser();
        $user->setMerchant([
            'service' => 'stripe',
            'id' => $result->id,
        ]);

        $this->save->setEntity($user)
            ->save();

        // Send a notification

        $this->notificationDelegate->onAccepted($account);

        return $account;
    }

    /**
     * Updates a stripe connect account
     * @param $account
     * @return string Account id
     * @throws \Exception
     */
    public function update(Account $account) : string
    {
        try {
            $stripeAccount = $this->accountInstance->retrieve($account->getId());
            if ($stripeAccount->individual->verification->status !== 'verified') {
                $stripeAccount->individual->first_name = $account->getFirstName();
                $stripeAccount->individual->last_name = $account->getLastName();

                $stripeAccount->individual->address->city = $account->getCity();
                $stripeAccount->individual->address->line1 = $account->getStreet();
                $stripeAccount->individual->address->postal_code = $account->getPostCode();
                $stripeAccount->individual->address->state = $account->getState();

                $dob = explode('-', $account->getDateOfBirth());
                $stripeAccount->individual->dob->day = $dob[2];
                $stripeAccount->individual->dob->month = $dob[1];
                $stripeAccount->individual->dob->year = $dob[0];

                if ($account->getGender()) {
                    $stripeAccount->individual->gender = $account->getGender();
                }

                if ($account->getPhoneNumber()) {
                    $stripeAccount->individual->phone = $account->getPhoneNumber();
                }
            } else {
                if (!($stripeAccount->individual->ssn_last_4i ?? null) && $account->getSSN()) {
                    $stripeAccount->individual->ssn_last_4 = $account->getSSN();
                }
            }

            if (!$account->individual->id_number_provided && $account->getPersonalIdNumber()) {
                $stripeAccount->individual->id_number = $account->getPersonalIdNumber();
            }

            if ($account->getEmail()) {
                $stripeAccount->individual->email = $account->getEmail();
            }

            if ($account->getUrl()) {
                $stripeAccount->business_profile->url = $account->getUrl();
            }

            if ($account->getPhoneNumber()) {
                $stripeAccount->individual->phone = $account->getPhoneNumber();
            }

            $stripeAccount->metadata = $account->getMetadata();

            $stripeAccount->save();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $stripeAccount->id;
    }

    /**
     * Updates a stripe connect account
     * @param $account
     * @return bool
     * @throws \Exception
     */
    public function acceptTos(Account $account) : bool
    {
        try {
            $this->accountInstance->update($account->getId(), [
               'tos_acceptance' => [
                   'date' => time(),
                   'ip' => $account->getIp(),
               ],
           ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add a bank account to stripe account
     * @param Account $account
     * @return boolean
     */
    public function addBankAccount(Account $account) : bool
    {
        $stripeAccount = $this->accountInstance->retrieve($account->getId());
        $stripeAccount->external_account = [
          'object' => 'bank_account',
          'account_number' => $account->getAccountNumber(),
          'country' => $account->getCountry(),
          'currency' => Currencies::byCountry($account->getCountry())
        ];

        if ($account->getRoutingNumber()) {
            $stripeAccount->external_account['routing_number'] = $account->getRoutingNumber();
        }

        try {
            $stripeAccount->save();
        } catch (Stripe\Error\InvalidRequest $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Remove bank account from stripe account
     * @param Account $account
     * @return bool
     */
    public function removeBankAccount(Account $account) : bool
    {
        $stripeAccount = $this->accountInstance->retrieve($account->getId());
        $bankAccountId = $stripeAccount->external_accounts->data[0]->id;

        try {
            $this->accountInstance->deleteExternalAccount($account->getId(), $bankAccountId);
        } catch (Stripe\Error\InvalidRequest $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Add document
     * @param Account $account
     * @param resource $file
     * @param string $documentType
     * @return bool
     */
    public function addDocument(Account $account, $file, string $documentType) : bool
    {
        $fileId =  $this->fileInstance->create([
            'purpose' => 'identity_document',
            'file' => $file,
        ], [ 'stripe_account' => $account->getId() ]);

        return (bool) $this->accountInstance->update($account->getId(), [
            'individual' => [
                'verification' => [
                    $documentType => [
                        'front' => $fileId,
                    ],
                ],
            ]
        ]);
    }

    /**
     * Return a stripe account
     * @param string $id
     * @return Account
     */
    public function getByAccountId(string $id) : ?Account
    {
        try {
            $result = $this->accountInstance->retrieve($id);
            $account = (new Account())
              ->setId($result->id)
              ->setStatus('active')
              ->setCountry($result->country)
              ->setFirstName($result->individual->first_name)
              ->setLastName($result->individual->last_name)
              ->setGender($result->individual->gender ?? null)
              ->setDateOfBirth($result->individual->dob->year . '-' . str_pad($result->individual->dob->month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($result->individual->dob->day, 2, '0', STR_PAD_LEFT))
              ->setStreet($result->individual->address->line1)
              ->setCity($result->individual->address->city)
              ->setPostCode($result->individual->address->postal_code)
              ->setState($result->individual->address->state)
              ->setPhoneNumber($result->individual->phone ?? null)
              ->setSSN($result->individual->ssn_last_4 ?? null)
              ->setPersonalIdNumber($result->individual->id_number ?? null)
              ->setBankAccount($result->external_accounts->data[0])
              ->setAccountNumber($result->external_accounts->data[0]['last4'])
              ->setRoutingNumber($result->external_accounts->data[0]['routing_number'])
              ->setDestination('bank')
              ->setPayoutInterval($result->settings->payouts->schedule->interval)
              ->setPayoutDelay($result->settings->payouts->schedule->delay_days)
              ->setPayoutAnchor($result->settings->payouts->schedule->monthly_anchor ?? null)
              ->setMetadata($result->metadata);

            //verifiction check
            if ($result->individual->verification->status === 'verified') {
                $account->setVerified(true);
            }

            if (!$account->getVerified()) {
                $account->setRequirement($result->requirements->currently_due[0]);
            }

            $account->setTotalBalance($this->getBalanceById($result->id, 'available'));
            $account->setPendingBalance($this->getBalanceById($result->id, 'pending'));
            $account->setTotalPaidOut($this->getTotalPaidOut($account));

            return $account;
        } catch (Stripe\Error\Permission $e) {
            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get account from user
     * @param User $user
     * @return Account
     */
    public function getByUser(User $user): ?Account
    {
        $merchant = $user->getMerchant();
        if (!$merchant || $merchant['service'] !== 'stripe') {
            return null;
        }
        return $this->getByAccountId($merchant['id']);
    }

    /**
     * Get balance by ID
     * @param string $id
     * @return Balance
     */
    public function getBalanceById(string $id, string $type) : Balance
    {
        $stripeBalance = $this->balanceInstance->retrieve([ 'stripe_account' => $id ]);
        $balance = new Balance();
        $balance->setAmount($stripeBalance->$type[0]->amount)
            ->setCurrency($stripeBalance->$type[0]->currency);
        return $balance;
    }

    protected function getTotalPaidOut(Account $account): Balance
    {
        $total = new Balance();
        foreach ($this->transactionsManager->setAccount($account)->getPayouts() as $payout) {
            $total->setCurrency($payout->getCurrency());
            $total->setAmount($total->getAmount() + ($payout->getGross() * -1));
        }
        return $total;
    }

    /**
     * Delete a merchant accont
     * @param Account $account
     * @return boolean
     */
    public function delete(Account $account) : bool
    {
        $stripeAccount = $this->accountInstance->retrieve($account->getId());
        $result = $stripeAccount->delete();

        if (!$result->deleted) {
            return false;
        }
    
        // Delete id from user entity

        $user = $account->getUser();
        $user->setMerchant([ 'deleted' => true ]);
        $this->save->setEntity($user)
            ->save();

        return true;
    }

    /**
     * Return an iterator of accounts
     * @return iterable
     */
    public function getList(): iterable
    {
        $startingAfter = null;
        while (true) {
            $opts = [
                'limit' => 20,
            ];
    
            $accounts = $this->accountInstance->all($opts);
            if (!$accounts) {
                return null;
            }
            foreach ($accounts->autoPagingIterator() as $account) {
                yield $account;
            }
        }
    }
}
