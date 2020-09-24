<?php

namespace Spec\Minds\Core\Monetization\Partners;

use Minds\Core\Monetization\Partners\Manager;
use Minds\Core\Monetization\Partners\Repository;
use Minds\Core\Monetization\Partners\EarningsDeposit;
use Minds\Core\Monetization\Partners\EarningsBalance;
use Minds\Core\Monetization\Partners\Delegates;
use Minds\Core\Plus;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Plus\Manager */
    protected $plusManager;

    public function let(
        Repository $repository,
        Plus\Manager $plusManager,
        Delegates\EmailDelegate $emailDelegate
    ) {
        $this->repository = $repository;
        $this->plusManager = $plusManager;
        $this->beConstructedWith($repository, null, null, $plusManager, null, null, null, $emailDelegate);
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

        $this->plusManager->getUnlocks($asOfTs)
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
}
