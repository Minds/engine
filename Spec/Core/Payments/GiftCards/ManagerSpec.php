<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\GiftCards;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Delegates\EmailDelegate;
use Minds\Core\Payments\GiftCards\Delegates\NotificationDelegate;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardStatusFilterEnum;
use Minds\Core\Payments\GiftCards\Manager;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\PaymentProcessor;
use Minds\Core\Payments\GiftCards\Repository;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $paymentsManagerMock;

    private Collaborator $paymentProcessorMock;
    private Collaborator $emailDelegateMock;
    private Collaborator $notificationDelegateMock;
    private Collaborator $loggerMock;
    private Collaborator $entitiesBuilderMock;

    public function let(
        Repository $repositoryMock,
        PaymentsManager $paymentsManagerMock,
        PaymentProcessor $paymentProcessor,
        EmailDelegate $emailDelegate,
        NotificationDelegate $notificationDelegate,
        Logger $logger,
        EntitiesBuilder $entitiesBuilder
    ): void {
        $this->repositoryMock = $repositoryMock;
        $this->paymentsManagerMock = $paymentsManagerMock;
        $this->paymentProcessorMock = $paymentProcessor;
        $this->emailDelegateMock = $emailDelegate;
        $this->notificationDelegateMock = $notificationDelegate;
        $this->loggerMock = $logger;
        $this->entitiesBuilderMock = $entitiesBuilder;

        $this->beConstructedWith(
            $this->repositoryMock,
            $this->paymentsManagerMock,
            $this->paymentProcessorMock,
            $this->emailDelegateMock,
            $this->loggerMock,
            $this->notificationDelegateMock,
            $this->entitiesBuilderMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Manager::class);
    }

    // get gift cards.
    public function it_should_get_gift_cards(
        User $claimedByUser,
        User $issuedByUser,
        GiftCard $giftCard1,
        GiftCard $giftCard2
    ): void {
        $productId = GiftCardProductIdEnum::BOOST;
        $statusFilter = GiftCardStatusFilterEnum::EXPIRED;
        $limit = 8;
        $ordering = GiftCardOrderingEnum::CREATED_DESC;

        $claimedByUser->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $issuedByUser?->getGuid()
            ->shouldBeCalled()
            ->willReturn(234);

        $this->repositoryMock->getGiftCards(
            123,
            234,
            $productId,
            $statusFilter,
            $limit,
            $ordering,
            null,
            null,
            null
        )
            ->shouldBeCalled()
            ->willReturn([$giftCard1, $giftCard2]);

        $this->getGiftCards(
            claimedByUser: $claimedByUser,
            issuedByUser: $issuedByUser,
            productId: $productId,
            statusFilter: $statusFilter,
            limit: $limit,
            ordering: $ordering
        )->shouldBe([$giftCard1, $giftCard2]);
    }

    // get gift cards ledger.
    public function it_should_get_gift_card_transaction_ledger(
        User $user,
        GiftCard $giftCard1,
        GiftCard $giftCard2
    ): void {
        $giftCardGuid = 234;
        $limit = 8;
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);
    
        $this->repositoryMock->getGiftCardTransactionLedger(
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn([$giftCard1, $giftCard2]);

        $this->getGiftCardTransactionLedger(
            user: $user,
            giftCardGuid: $giftCardGuid,
            limit: $limit
        )->shouldBe([$giftCard1, $giftCard2]);
    }
    
    public function it_should_create_gift_card(User $issuer): void
    {
        $expiresAt = strtotime('+90 days');

        $issuer->getGuid()->willReturn('1244987032468459522');

        $this->paymentProcessorMock->setupPayment(Argument::type(GiftCard::class), Argument::type("string"))
            ->shouldBeCalledOnce()
            ->willReturn("payment_intent_id");

        $this->paymentProcessorMock->capturePayment("payment_intent_id", $issuer)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->paymentsManagerMock->createPayment(Argument::type(PaymentDetails::class))
            ->shouldBeCalledOnce();

        $this->repositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->repositoryMock->addGiftCard(Argument::any())->shouldBeCalledOnce();

        $this->repositoryMock->addGiftCardTransaction(Argument::any())->shouldBeCalledOnce();

        $this->repositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->createGiftCard($issuer, GiftCardProductIdEnum::BOOST, 9.99, "", $expiresAt);
    }

    public function it_should_return_a_gift_card(GiftCard $giftCard): void
    {
        $this->repositoryMock->getGiftCard(1244987032468459522)->willReturn($giftCard);
        $this->getGiftCard(1244987032468459522)->shouldReturn($giftCard);
    }

    public function it_should_return_a_gift_card_by_claim_code(GiftCard $giftCard): void
    {
        $this->repositoryMock->getGiftCardByClaimCode('~claimCode~')->willReturn($giftCard);
        $this->getGiftCardByClaimCode('~claimCode~')->shouldReturn($giftCard);
    }

    public function it_should_claim_a_gift_card(User $claimer): void
    {
        $refTime = time();
        $giftCard = new GiftCard(1244987032468459522, GiftCardProductIdEnum::BOOST, 10, 1244987032468459522, $refTime, 'claim-me', strtotime('+1 year', $refTime));

        $this->repositoryMock->getGiftCardByClaimCode("claim-me")
            ->shouldBeCalledOnce()
            ->willReturn($giftCard);

        $this->repositoryMock->updateGiftCardClaim(Argument::type(GiftCard::class))->willReturn(true);

        $claimer->getGuid()->willReturn('1244987032468459523');

        $this->claimGiftCard($claimer, 'claim-me')->shouldReturn($giftCard);
    }
}
