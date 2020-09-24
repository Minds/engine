<?php

namespace Spec\Minds\Core\Payments\Stripe\Connect;

use Minds\Core\Payments\Stripe\Connect\Manager;
use Minds\Core\Payments\Stripe\Connect\Account;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Payments\Stripe\Connect\Delegates\NotificationDelegate;
use Minds\Core\Payments\Stripe\Instances\AccountInstance;
use Minds\Core\Payments\Stripe\Instances\BalanceInstance;
use Minds\Core\Payments\Stripe\Instances\FileInstance;
use Minds\Core\Payments\Stripe\Transactions;
use Minds\Common\Repository\Response;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $save;
    private $notificationDelegate;
    private $accountInstance;
    private $balanceInstance;
    private $fileInstance;

    /** @var Transactions\Manager */
    private $transactionsManager;

    public function let(
        Save $save,
        NotificationDelegate $notificationDelegate,
        AccountInstance $accountInstance,
        BalanceInstance $balanceInstance,
        FileInstance $fileInstance,
        Transactions\Manager $transactionsManager
    ) {
        $this->beConstructedWith($save, $notificationDelegate, $accountInstance, $balanceInstance, $fileInstance, $transactionsManager);
        $this->save = $save;
        $this->notificationDelegate = $notificationDelegate;
        $this->accountInstance = $accountInstance;
        $this->balanceInstance = $balanceInstance;
        $this->fileInstance = $fileInstance;
        $this->transactionsManager = $transactionsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_add_a_new_account_to_stripe(Account $account)
    {
        $account->getDateOfBirth()
            ->willReturn('1943-02-25');
        $account->getFirstName()
            ->willReturn('George');
        $account->getLastName()
            ->willReturn('Harrison');
        $account->getCity()
            ->willReturn('Liverpool');
        $account->getCountry()
            ->willReturn('GB');
        $account->getStreet()
            ->willReturn('12 Arnold Grove');
        $account->getState()
            ->willReturn('Lancashire');
        $account->getPostCode()
            ->willReturn('L15 8HP');
        $account->getIp()
            ->willReturn('45.01.99.99');
        $account->getGender()
            ->willReturn(null);
        $account->getPhoneNumber()
            ->willReturn('01000000000');
        $account->getSSN()
            ->willReturn(null);
        $account->getPersonalIdNumber()
            ->willReturn(null);
        $account->getPayoutInterval()
            ->willReturn('monthly');
        $account->getEmail()
            ->willReturn('test@minds.com');
        $account->getUrl()
            ->willReturn('https://minds.com/test');
        $account->getMetadata()
            ->willReturn([
                'user_guid' => '123'
            ]);
        
        $this->accountInstance->create(Argument::any())
            ->shouldBeCalled()
            ->willReturn((object) [
                'id' => 'account_id',
            ]);

        $account->setId('account_id')
            ->shouldBeCalled()
            ->willReturn($account);

        $user = new User();
        $account->getUser()
            ->willReturn($user);
        
        $this->save
            ->setEntity($user)
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save
            ->save()
            ->shouldBeCalled();

        $this->add($account)
            ->shouldReturn($account);
    }

    public function it_should_get_an_account_by_an_id()
    {
        $this->accountInstance->retrieve('acc_123')
            ->shouldBeCalled()
            ->willReturn((object) [
                'id' => 'acc_123',
                'country' => 'GB',
                'individual' => (object) [
                    'first_name' => 'George',
                    'last_name' => 'Harrison',
                    'gender' => null,
                    'dob' => (object) [
                        'year' => 1943,
                        'month' => 2,
                        'day' => 25,
                    ],
                    'address' => (object) [
                        'line1' => '12 Arnold Grove',
                        'city' => 'Liverpool',
                        'state' => 'Lancashire',
                        'postal_code' => 'L15 8HP',
                    ],
                    'phone_number' => '0112',
                    'ssn_last_4' => null,
                    'personal_id_number' => null,
                    'verification' => (object) [
                        'status' => 'verified',
                    ],
                ],
                'external_accounts' => (object) [
                    'data' => [
                        [
                            'last4' => 6789,
                            'routing_number' => 123456789,
                        ],
                    ],
                ],
                'verification' => (object) [
                    'disabled_reason' => null,
                ],
                'settings' => (object) [
                    'payouts' => (object) [
                        'schedule' => (object) [
                            'interval' => 1,
                            'delay_days' => 2,
                            'monthly_anchor' => 31,
                        ],
                    ],
                ],
                'metadata' => [],
            ]);

        $this->balanceInstance->retrieve([ 'stripe_account' => 'acc_123'])
            ->willReturn((object) [
                'pending' => [
                    (object) [
                        'amount' => 100,
                        'currency' => 'GBP',
                    ],
                ],
                'available' => [
                    (object) [
                        'amount' => 100,
                        'currency' => 'GBP',
                    ],
                ],
            ]);

        $this->transactionsManager->setAccount(Argument::any())
            ->willReturn($this->transactionsManager);

        $this->transactionsManager->getPayouts()
            ->willReturn(new Response());

        $account = $this->getByAccountId('acc_123');
        $account->getId()
            ->shouldBe('acc_123');
        $account->getFirstName()
            ->shouldBe('George');
        $account->getLastName()
            ->shouldBe('Harrison');
        $account->getStreet()
            ->shouldBe('12 Arnold Grove');
        $account->getCity()
            ->shouldBe('Liverpool');
    }

    public function it_should_get_an_account_from_a_user_entity()
    {
        $this->accountInstance->retrieve('acc_123')
            ->shouldBeCalled()
            ->willReturn((object) [
                'id' => 'acc_123',
                'country' => 'GB',
                'individual' => (object) [
                    'first_name' => 'George',
                    'last_name' => 'Harrison',
                    'gender' => null,
                    'dob' => (object) [
                        'year' => 1943,
                        'month' => 2,
                        'day' => 25,
                    ],
                    'address' => (object) [
                        'line1' => '12 Arnold Grove',
                        'city' => 'Liverpool',
                        'state' => 'Lancashire',
                        'postal_code' => 'L15 8HP',
                    ],
                    'phone_number' => '0112',
                    'ssn_last_4' => null,
                    'personal_id_number' => null,
                    'verification' => (object) [
                        'status' => 'verified',
                    ],
                ],
                'external_accounts' => (object) [
                    'data' => [
                        [
                            'last4' => 6789,
                            'routing_number' => 123456789,
                        ],
                    ],
                ],
                'verification' => (object) [
                    'disabled_reason' => null,
                ],
                'settings' => (object) [
                    'payouts' => (object) [
                        'schedule' => (object) [
                            'interval' => 1,
                            'delay_days' => 2,
                            'monthly_anchor' => 31,
                        ]
                    ]
                ],
                'metadata' => [],
            ]);

        $this->balanceInstance->retrieve([ 'stripe_account' => 'acc_123'])
            ->willReturn((object) [
                'pending' => [
                    (object) [
                        'amount' => 100,
                        'currency' => 'GBP',
                    ],
                ],
                'available' => [
                    (object) [
                        'amount' => 100,
                        'currency' => 'GBP',
                    ],
                ],
            ]);

        $this->transactionsManager->setAccount(Argument::any())
            ->willReturn($this->transactionsManager);

        $this->transactionsManager->getPayouts()
            ->willReturn(new Response());

        $user = new User();
        $user->setMerchant([
            'service' => 'stripe',
            'id' => 'acc_123',
        ]);

        $account = $this->getByUser($user);
    }

    public function it_should_not_get_an_account_if_not_stripe_service()
    {
        $user = new User();
        $user->setMerchant([
            'service' => 'bitcoin',
            'id' => 'acc_123',
        ]);

        $account = $this->getByUser($user);
        $account->shouldBe(null);
    }

    public function it_should_get_application_fees()
    {
        $this->getApplicationFees([ 'from' => time() ]);
    }
}
