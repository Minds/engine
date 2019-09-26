<?php

namespace Minds\Core\Payments\Stripe\Connect;

use Minds\Core\Entities\Actions\Save;
use Minds\Core\Payments\Stripe\Connect\Delegates\NotificationDelegate;
use Minds\Core\Payments\Stripe\Currencies;
use Minds\Core\Payments\Stripe\Instances\AccountInstance;
use Minds\Core\Payments\Stripe\Instances\BalanceInstance;
use Minds\Core\Payments\Stripe\Instances\FileInstance;
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

    public function __construct(
        Save $save = null,
        NotificationDelegate $notificationDelegate = null,
        AccountInstance $accountInstance = null,
        BalanceInstance $balanceInstance = null,
        FileInstance $fileInstance = null
    ) {
        $this->save = $save ?: new Save();
        $this->notificationDelegate = $notificationDelegate ?: new NotificationDelegate();
        $this->accountInstance = $accountInstance ?: new AccountInstance();
        $this->balanceInstance = $balanceInstance ?: new BalanceInstance();
        $this->fileInstance = $fileInstance ?: new FileInstance();
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
          'tos_acceptance' => [
            'date' => time(),
            'ip' => $account->getIp(),
          ]
        ];

        // Required for JP only

        if ($account->getGender()) {
            $data['legal_entity']['gender'] = $account->getGender();
        }

        if ($account->getPhoneNumber()) {
            $data['legal_entity']['phone_number'] = $account->getPhoneNumber();
        }

        // US 1099 requires SSN

        if ($account->getSSN()) {
            $data['legal_entity']['ssn_last_4'] = $account->getSSN();
        }

        if ($account->getPersonalIdNumber()) {
            $data['legal_entity']['personal_id_number'] = $account->getPersonalIdNumber();
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

            if ($stripeAccount->legal_entity->verification->status !== 'verified') {
                $stripeAccount->legal_entity->first_name = $account->getFirstName();
                $stripeAccount->legal_entity->last_name = $account->getLastName();

                $stripeAccount->legal_entity->address->city = $account->getCity();
                $stripeAccount->legal_entity->address->line1 = $account->getStreet();
                $stripeAccount->legal_entity->address->postal_code = $account->getPostCode();
                $stripeAccount->legal_entity->address->state = $account->getState();

                $dob = explode('-', $account->getDateOfBirth());
                $stripeAccount->legal_entity->dob->day = $dob[2];
                $stripeAccount->legal_entity->dob->month = $dob[1];
                $stripeAccount->legal_entity->dob->year = $dob[0];

                if ($account->getGender()) {
                    $stripeAccount->legal_entity->gender = $account->getGender();
                }

                if ($account->getPhoneNumber()) {
                    $stripeAccount->legal_entity->phone_number = $account->getPhoneNumber();
                }
            } else {
                if (!$stripeAccount->legal_entity->ssn_last_4_provided && $account->getSSN()) {
                    $stripeAccount->legal_entity->ssn_last_4 = $account->getSSN();
                }

                if (!$account->legal_entity->personal_id_number_provided && $account->getPersonalIdNumber()) {
                    $account->legal_entity->personal_id_number = $account->getPersonalIdNumber();
                }
            }

            if ($account->getAccountNumber()) {
                $stripeAccount->external_account->account_number = $account->getAccountNumber();
            }

            if ($account->getRoutingNumber()) {
                $stripeAccount->external_account->routing_number = $account->getRoutingNumber();
            }

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
     * Add photo Id
     * @param Account $account
     * @param resource $file
     * @return bool
     */
    public function addPhotoId(Account $account, $file) : bool
    {
        return (bool) $this->fileInstance->create([
            'purpose' => 'identity_document',
            'file' => $file,
        ], [ 'stripe_account' => $account->getId() ]);
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
              ->setFirstName($result->legal_entity->first_name)
              ->setLastName($result->legal_entity->last_name)
              ->setGender($result->legal_entity->gender)
              ->setDateOfBirth($result->legal_entity->dob->year . '-' . str_pad($result->legal_entity->dob->month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($result->legal_entity->dob->day, 2, '0', STR_PAD_LEFT))
              ->setStreet($result->legal_entity->address->line1)
              ->setCity($result->legal_entity->address->city)
              ->setPostCode($result->legal_entity->address->postal_code)
              ->setState($result->legal_entity->address->state)
              ->setPhoneNumber($result->legal_entity->phone_number)
              ->setSSN($result->legal_entity->ssn_last_4)
              ->setPersonalIdNumber($result->legal_entity->personal_id_number)
              ->setBankAccount($result->external_accounts->data[0])
              ->setAccountNumber($result->external_accounts->data[0]['last4'])
              ->setRoutingNumber($result->external_accounts->data[0]['routing_number'])
              ->setDestination('bank')
              ->setPayoutInterval($result->settings->payouts->schedule->interval)
              ->setPayoutDelay($result->settings->payouts->schedule->delay_days)
              ->setPayoutAnchor($result->settings->payouts->schedule->monthly_anchor);

            //verifiction check
            if ($result->legal_entity->verification->status === 'verified') {
                $account->setVerified(true);
            }

            if (!$account->getVerified()) {
                switch ($result->requirements->disabled_reason) {
                    case 'requirements.past_due':
                        $account->setRequirement($result->requirements->currently_due[0]);
                        break;
                }
            }

            $account->setTotalBalance($this->getBalanceById($result->id, 'available'));
            $account->setPendingBalance($this->getBalanceById($result->id, 'pending'));

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
}
