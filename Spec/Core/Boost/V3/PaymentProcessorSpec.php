<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3;

use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Boost\V3\Onchain\AdminTransactionProcessor;
use Minds\Core\Boost\V3\PaymentProcessor;
use Minds\Core\Guid;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\V2\Enums\FreePaymentMethodEnum;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Prophecy\Argument;

class PaymentProcessorSpec extends ObjectBehavior
{
    private Collaborator $intentsManagerV2Mock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $offchainTransactionsMock;
    private Collaborator $mindsConfigMock;
    private Collaborator $adminTransactionProcessorMock;
    private Collaborator $paymentsManagerMock;
    private Collaborator $giftCardsManagerMock;

    public function let(
        IntentsManagerV2 $intentsManagerV2Mock,
        EntitiesBuilder $entitiesBuilderMock,
        OffchainTransactions $offchainTransactionsMock,
        MindsConfig $mindsConfigMock,
        AdminTransactionProcessor $adminTransactionProcessorMock,
        PaymentsManager $paymentsManagerMock,
        GiftCardsManager $giftCardsManagerMock
    ) {
        $this->intentsManagerV2Mock = $intentsManagerV2Mock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->offchainTransactionsMock = $offchainTransactionsMock;
        $this->mindsConfigMock = $mindsConfigMock;
        $this->adminTransactionProcessorMock = $adminTransactionProcessorMock;
        $this->paymentsManagerMock = $paymentsManagerMock;
        $this->giftCardsManagerMock = $giftCardsManagerMock;

        $this->beConstructedWith(
            $intentsManagerV2Mock,
            $entitiesBuilderMock,
            $offchainTransactionsMock,
            $mindsConfigMock,
            $adminTransactionProcessorMock,
            $paymentsManagerMock,
            $giftCardsManagerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(PaymentProcessor::class);
    }

    public function it_should_setup_cash_payment(
        Boost $boost,
        User $user
    ): void {
        $paymentDetails = new PaymentDetails();
        $paymentMethod = 'tk_card';
        $boostOwnerGuid = 1234567890123456;
        $boostGuid = 2234567890123456;
        $boostEntityGuid = 3234567890123456;
        $boostTargetLocation = BoostTargetLocation::NEWSFEED;
        $userUsername = 'testuser';
        $boostPaymentAmount = 123;

        $boost->getPaymentAmount()
          ->shouldBeCalled()
          ->willReturn($boostPaymentAmount);

        $boost->getPaymentMethod()
          ->shouldBeCalled()
          ->willReturn(BoostPaymentMethod::CASH);

        $boost->getPaymentMethodId()
          ->shouldBeCalled()
          ->willReturn($paymentMethod);

        $boost->getPaymentTxId()
          ->shouldBeCalled()
          ->willReturn(null);

        $boost->getOwnerGuid()
          ->shouldBeCalled()
          ->willReturn($boostOwnerGuid);

        $boost->getGuid()
          ->shouldBeCalled()
          ->willReturn($boostGuid);

        $boost->getEntityGuid()
          ->shouldBeCalled()
          ->willReturn($boostEntityGuid);

        $boost->getTargetLocation()
          ->shouldBeCalled()
          ->willReturn($boostTargetLocation);

        $user->getUsername()
          ->shouldBeCalled()
          ->willReturn($userUsername);

        $this->entitiesBuilderMock->single($boostOwnerGuid)
          ->shouldBeCalled()
          ->willReturn($user);

        $boost->setPaymentTxId(Argument::any())
          ->shouldBeCalled();

        $boost->setPaymentGuid(Argument::any())
          ->shouldBeCalled();

        $this->intentsManagerV2Mock->add(Argument::any())
          ->shouldBeCalled();

        $this->setupBoostPayment($boost, $user, $paymentDetails)
          ->shouldBe(true);
    }

    public function it_should_setup_cash_payment_for_free_admin_boost(
        Boost $boost,
        User $user
    ): void {
        $paymentDetails = new PaymentDetails();

        $boost->getPaymentMethod()
          ->shouldBeCalled()
          ->willReturn(BoostPaymentMethod::CASH);

        $boost->getPaymentMethodId()
          ->shouldBeCalled()
          ->willReturn(FreePaymentMethodEnum::FREE_ADMIN_BOOST->value);

        $boost->getPaymentTxId()
          ->shouldBeCalled()
          ->willReturn(null);

        $user->isAdmin()
          ->shouldBeCalled()
          ->willReturn(true);

        $boost->setPaymentTxId(FreePaymentMethodEnum::FREE_ADMIN_BOOST->value)
          ->shouldBeCalled();

        $boost->setPaymentGuid(Argument::any())
          ->shouldBeCalled();

        $this->setupBoostPayment($boost, $user, $paymentDetails)
          ->shouldBe(true);
    }

    public function it_should_throw_exception_when_setting_up_cash_payment_for_free_admin_boost_when_not_admin(
        Boost $boost,
        User $user
    ): void {
        $paymentDetails = new PaymentDetails();

        $boost->getPaymentMethod()
          ->shouldBeCalled()
          ->willReturn(BoostPaymentMethod::CASH);

        $boost->getPaymentMethodId()
          ->shouldBeCalled()
          ->willReturn(FreePaymentMethodEnum::FREE_ADMIN_BOOST->value);

        $user->isAdmin()
          ->shouldBeCalled()
          ->willReturn(false);

        $this->shouldThrow(new ForbiddenException('Only admins can create free admin Boosts'))->duringSetupBoostPayment($boost, $user, $paymentDetails);
    }
}
