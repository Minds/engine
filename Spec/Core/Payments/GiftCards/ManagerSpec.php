<?php

namespace Spec\Minds\Core\Payments\GiftCards;

use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Manager;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Repository;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $repositoryMock;
    private $paymentsManagerMock;

    function let(Repository $repositoryMock, PaymentsManager $paymentsManagerMock)
    {
        $this->beConstructedWith($repositoryMock, $paymentsManagerMock);
        $this->repositoryMock = $repositoryMock;
        $this->paymentsManagerMock = $paymentsManagerMock;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    function it_should_create_gift_card(User $issuer)
    {
        $expiresAt = strtotime('+90 days');

        $issuer->getGuid()->willReturn('1244987032468459522');

        $this->repositoryMock->beginTransaction()
            ->shouldBeCalled();

        $this->repositoryMock->addGiftCard(Argument::any())->shouldBeCalled();

        $this->repositoryMock->addGiftCardTransaction(Argument::any())->shouldBeCalled();

        $this->repositoryMock->commitTransaction()
            ->shouldBeCalled();

        $this->createGiftCard($issuer, GiftCardProductIdEnum::BOOST, 9.99, $expiresAt);
    }

    function it_should_return_a_gift_card(GiftCard $giftCard)
    {
        $this->repositoryMock->getGiftCard(1244987032468459522)->willReturn($giftCard);
        $this->getGiftCard(1244987032468459522)->shouldReturn($giftCard);
    }

    function it_should_claim_a_gift_card(User $claimer)
    {
        $refTime = time();
        $giftCard = new GiftCard(1244987032468459522, GiftCardProductIdEnum::BOOST, 10, 1244987032468459522, $refTime, 'claim-me', strtotime('+1 year', $refTime));

        $this->repositoryMock->updateGiftCardClaim(Argument::type(GiftCard::class))->willReturn(true);

        $claimer->getGuid()->willReturn('1244987032468459523');

        $this->claimGiftCard($giftCard, $claimer, 'claim-me')->shouldReturn(true);
    }
}
