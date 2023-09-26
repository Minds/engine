<?php

namespace Spec\Minds\Core\Monetization\Partners;

use Minds\Core\Boost\V3\Partners\Manager as BoostPartnersManager;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Monetization\Partners\Delegates;
use Minds\Core\Monetization\Partners\Delegates\DepositsDelegate;
use Minds\Core\Monetization\Partners\EarningsBalance;
use Minds\Core\Monetization\Partners\EarningsDeposit;
use Minds\Core\Monetization\Partners\Manager;
use Minds\Core\Monetization\Partners\RelationalRepository;
use Minds\Core\Payments\Stripe;
use Minds\Core\Payments\V2\Enums\PaymentMethod;
use Minds\Core\Payments\V2\Enums\PaymentStatus;
use Minds\Core\Payments\V2\Enums\PaymentType;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Core\Payments\V2\PaymentOptions;
use Minds\Core\Plus;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\CommonMatchers;

class ManagerSpec extends ObjectBehavior
{
    use CommonMatchers;

    /** @var RelationalRepository */
    protected $repository;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Plus\Manager */
    protected $plusManager;

    /** @var Stripe\Connect\Manager */
    protected $connectManager;

    private Collaborator $boostPartnersManager;
    private Collaborator $paymentsManager;
    private Collaborator $depositsDelegate;

    public function let(
        RelationalRepository $repository,
        EntitiesBuilder $entitiesBuilder,
        Plus\Manager $plusManager,
        Stripe\Connect\Manager $connectManager,
        Delegates\EmailDelegate $emailDelegate,
        BoostPartnersManager $boostPartnersManager,
        PaymentsManager $paymentsManager,
        DepositsDelegate $depositsDelegate
    ) {
        $this->repository = $repository;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->plusManager = $plusManager;
        $this->connectManager = $connectManager;
        $this->boostPartnersManager = $boostPartnersManager;
        $this->paymentsManager = $paymentsManager;
        $this->depositsDelegate = $depositsDelegate;

        $this->beConstructedWith(
            $this->repository,
            null,
            $entitiesBuilder,
            $plusManager,
            $connectManager,
            null,
            null,
            $emailDelegate,
            $this->boostPartnersManager,
            $paymentsManager,
            $depositsDelegate
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_add_earnings_to_repository()
    {
        $deposit = new EarningsDeposit();

        $this->repository->add($deposit)
            ->shouldBeCalled();

        $this->add($deposit)
            ->shouldBe(true);
    }

    public function it_should_issue_deposits()
    {
        $asOfTs = strtotime('midnight 1st June 2020');
        // Plus
        $this->plusManager->getDailyRevenue($asOfTs)
            ->willReturn(10); // $10 USD

        $this->plusManager->getScores($asOfTs)
            ->willReturn([
                [
                    'user_guid' => 123,
                    'sharePct' => 0.5,
                ],
                [
                    'user_guid' => 456,
                    'sharePct' => 0.3,
                ],
                [
                    'user_guid' => 789,
                    'sharePct' => 0.2,
                ],
            ]);

        // Wire referrals
        $this->connectManager->getApplicationFees([
            'from' => $asOfTs
        ])
            ->willReturn($this->getMockApplicationFees());
        // Return a mock Stripe\Connect\Account instance
        $this->connectManager->getByAccountId('acct_t1')
            ->willReturn(
                (new Stripe\Connect\Account)
                ->setMetadata([
                    'guid' => 123
                ])
            );
        // Return a mock User for our account
        $mockUser1 = new User();
        $mockUser1->referrer = 456;
        $this->entitiesBuilder->single(123)
            ->willReturn($mockUser1);

        $this->repository->add(Argument::that(function ($deposit) {
            return $deposit->getAmountCents() === (float) 500; // 1000 fee / 2
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->add(Argument::that(function ($deposit) {
            return $deposit->getAmountCents() === (float) 125;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->add(Argument::that(function ($deposit) {
            return $deposit->getAmountCents() === (float) 75;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->add(Argument::that(function ($deposit) {
            return $deposit->getAmountCents() === (float) 50;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        // Pageviews

        // Referrals

        $response = $this->issueDeposits([
            'from' => $asOfTs,
        ]);

        $response->current()
            ->getAmountCents()
            ->shouldBe((float) 500); // 1000 cent wire fee / 2 (50% share)
        //
        $response->next();
        $response->current()
            ->getAmountCents()
            ->shouldBe((float) 125);
        //
        $response->next();
        $response->current()
            ->getAmountCents()
            ->shouldBe((float) 75);
        //
        $response->next();
        $response->current()
            ->getAmountCents()
            ->shouldBe((float) 50);
    }

    public function it_should_get_balance_object(User $user)
    {
        $user->getGuid()
            ->willReturn(123);

        $this->repository->getBalance("123", null)
            ->willReturn((new EarningsBalance)->setAmountCents(100));

        $balance = $this->getBalance($user);
        $balance->getAmountCents()->shouldBe(100);
    }

    public function it_should_issue_affiliate_deposits(User $user)
    {
        $fromTimestamp = time();
        $toTimestamp = time();
        $affiliateGuid = '1234567890123456';
        $totalEarningsMillis = 999999;

        $paymentOptions = (new PaymentOptions())
            ->setWithAffiliate(true)
            ->setFromTimestamp($fromTimestamp)
            ->setToTimestamp($toTimestamp)
            ->setPaymentTypes([
                PaymentType::MINDS_PRO_PAYMENT,
                PaymentType::BOOST_PAYMENT,
                PaymentType::MINDS_PLUS_PAYMENT
            ])
            ->setPaymentStatus(PaymentStatus::COMPLETED)
            ->setPaymentMethod(PaymentMethod::CASH);

        $deposit = (new EarningsDeposit())
            ->setTimestamp($toTimestamp)
            ->setUserGuid($affiliateGuid)
            ->setAmountCents($totalEarningsMillis / 10)
            ->setItem('affiliate');

        $opts = [
            'from' => $fromTimestamp,
            'to' => $toTimestamp,
        ];

        $this->paymentsManager->getPaymentsAffiliatesEarnings(
            $paymentOptions
        )
            ->shouldBeCalled()
            ->willYield([[
                'affiliate_user_guid' => $affiliateGuid,
                'total_earnings_millis' => $totalEarningsMillis,
            ]]);

        $this->repository->add(Argument::any())
                ->shouldBeCalled();

        $this->entitiesBuilder->single($affiliateGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->depositsDelegate->onIssueAffiliateDeposit($user, $deposit)
            ->shouldBeCalled();
            
        $this->issueAffiliateDeposits($opts)->shouldBeAGeneratorWithValues([
            $deposit
        ]);
    }

    public function it_should_issue_affiliate_deposits_with_no_notification_if_amount_is_0(User $user)
    {
        $fromTimestamp = time();
        $toTimestamp = time();
        $affiliateGuid = '1234567890123456';
        $totalEarningsMillis = 0;

        $paymentOptions = (new PaymentOptions())
            ->setWithAffiliate(true)
            ->setFromTimestamp($fromTimestamp)
            ->setToTimestamp($toTimestamp)
            ->setPaymentTypes([
                PaymentType::MINDS_PRO_PAYMENT,
                PaymentType::BOOST_PAYMENT,
                PaymentType::MINDS_PLUS_PAYMENT
            ])
            ->setPaymentStatus(PaymentStatus::COMPLETED)
            ->setPaymentMethod(PaymentMethod::CASH);

        $deposit = (new EarningsDeposit())
            ->setTimestamp($toTimestamp)
            ->setUserGuid($affiliateGuid)
            ->setAmountCents($totalEarningsMillis / 10)
            ->setItem('affiliate');

        $opts = [
            'from' => $fromTimestamp,
            'to' => $toTimestamp,
        ];

        $this->paymentsManager->getPaymentsAffiliatesEarnings(
            $paymentOptions
        )
            ->shouldBeCalled()
            ->willYield([[
                'affiliate_user_guid' => $affiliateGuid,
                'total_earnings_millis' => $totalEarningsMillis,
            ]]);

        $this->repository->add(Argument::any())
                ->shouldBeCalled();

        $this->entitiesBuilder->single($affiliateGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->depositsDelegate->onIssueAffiliateDeposit($user, $deposit)
            ->shouldNotBeCalled();
            
        $this->issueAffiliateDeposits($opts)->shouldBeAGeneratorWithValues([
            $deposit
        ]);
    }

    /**
     * @return \Stripe\ApplicationFee[]
     */
    private function getMockApplicationFees(): array
    {
        $fee1 = (new \Stripe\ApplicationFee);
        $fee1->account = 'acct_t1';
        $fee1->amount = 1000;

        return [
            $fee1,
        ];
    }
}
