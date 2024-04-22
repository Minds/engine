<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\GiftCards\Delegates;

use Minds\Common\SystemUser;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Delegates\NotificationDelegate;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Types\GiftCardTarget;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class NotificationDelegateSpec extends ObjectBehavior
{
    private Collaborator $actionEventsTopic;
    private Collaborator $entitiesBuilder;
    private Collaborator $experimentsManager;
    private Collaborator $logger;

    public function let(
        ActionEventsTopic $actionEventsTopic,
        EntitiesBuilder $entitiesBuilder,
        ExperimentsManager $experimentsManager,
        Logger $logger
    ): void {
        $this->actionEventsTopic = $actionEventsTopic;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->experimentsManager = $experimentsManager;
        $this->logger = $logger;

        $this->beConstructedWith(
            $actionEventsTopic,
            $entitiesBuilder,
            $experimentsManager,
            $logger
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(NotificationDelegate::class);
    }

    public function it_should_send_an_action_event_on_gift_card_creation(
    ): void {
        $targetUserGuid = 1234567890123456;
        $giftCardIssuedByGuid = 2234567890123456;
        $giftCardGuid = 3234567890123456;

        $recipient = new GiftCardTarget(
            targetUserGuid: $targetUserGuid
        );

        $giftCard = new GiftCard(
            guid: $giftCardGuid,
            productId: GiftCardProductIdEnum::PLUS,
            amount: 9.99,
            issuedByGuid: $giftCardIssuedByGuid,
            issuedAt: time(),
            claimCode: 'change-me',
            expiresAt: strtotime('+1 year', time()),
            claimedByGuid: 1244987032468459524,
            claimedAt: time(),
            balance: 9.99,
        );

        $recipientUser = new User(
            guid: $targetUserGuid,
        );

        $this->entitiesBuilder->single($targetUserGuid)
            ->shouldBeCalled()
            ->willReturn($recipientUser);

        $this->actionEventsTopic->send(
            (new ActionEvent())
                ->setUser(new SystemUser())
                ->setAction(ActionEvent::ACTION_GIFT_CARD_RECIPIENT_NOTIFICATION)
                ->setEntity($recipientUser)
                ->setActionData([
                    'gift_card_guid' => $giftCardGuid,
                    'sender_guid' => $giftCardIssuedByGuid,
                ])
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onCreateGiftCard($giftCard, $recipient);
    }

    public function it_should_NOT_send_an_action_event_on_gift_card_creation_when_no_target_user_guid(
    ): void {
        $targetUserGuid = 1234567890123456;
        $giftCardIssuedByGuid = 2234567890123456;
        $giftCardGuid = 3234567890123456;

        $recipient = new GiftCardTarget(
            targetUserGuid: null
        );

        $giftCard = new GiftCard(
            guid: $giftCardGuid,
            productId: GiftCardProductIdEnum::PLUS,
            amount: 9.99,
            issuedByGuid: $giftCardIssuedByGuid,
            issuedAt: time(),
            claimCode: 'change-me',
            expiresAt: strtotime('+1 year', time()),
            claimedByGuid: 1244987032468459524,
            claimedAt: time(),
            balance: 9.99,
        );

        $recipientUser = new User(
            guid: $targetUserGuid,
        );

        $this->actionEventsTopic->send(Argument::any())
            ->shouldNotBeCalled();

        $this->onCreateGiftCard($giftCard, $recipient);
    }

    public function it_should_NOT_send_an_action_event_on_gift_card_creation_when_experiment_is_off(
    ): void {
        $targetUserGuid = 1234567890123456;
        $giftCardIssuedByGuid = 2234567890123456;
        $giftCardGuid = 3234567890123456;

        $recipient = new GiftCardTarget(
            targetUserGuid: null
        );

        $giftCard = new GiftCard(
            guid: $giftCardGuid,
            productId: GiftCardProductIdEnum::PLUS,
            amount: 9.99,
            issuedByGuid: $giftCardIssuedByGuid,
            issuedAt: time(),
            claimCode: 'change-me',
            expiresAt: strtotime('+1 year', time()),
            claimedByGuid: 1244987032468459524,
            claimedAt: time(),
            balance: 9.99,
        );

        $recipientUser = new User(
            guid: $targetUserGuid,
        );

        $this->actionEventsTopic->send(Argument::any())
            ->shouldNotBeCalled();

        $this->onCreateGiftCard($giftCard, $recipient);
    }

    public function it_should_send_an_action_event_on_gift_card_claim(
        User $claimant
    ): void {
        $claimantGuid = 1234567890123456;
        $issuerGuid = 2234567890123456;
        $giftCardGuid = 3234567890123456;

        $issuer = new User($issuerGuid);

        $claimant->getGuid()
            ->shouldBeCalled()
            ->willReturn($claimantGuid);

        $giftCard = new GiftCard(
            guid: $giftCardGuid,
            productId: GiftCardProductIdEnum::PLUS,
            amount: 9.99,
            issuedByGuid: $issuerGuid,
            issuedAt: time(),
            claimCode: 'change-me',
            expiresAt: strtotime('+1 year', time()),
            claimedByGuid: 1244987032468459524,
            claimedAt: time(),
            balance: 9.99,
        );

        $this->entitiesBuilder->single($issuerGuid)
            ->shouldBeCalled()
            ->willReturn($issuer);

        $this->actionEventsTopic->send(
            (new ActionEvent())
                ->setUser(new SystemUser())
                ->setAction(ActionEvent::ACTION_GIFT_CARD_ISSUER_CLAIMED_NOTIFICATION)
                ->setEntity($issuer)
                ->setActionData([
                    'gift_card_guid' => $giftCardGuid,
                    'claimant_guid' => $claimantGuid,
                ])
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onGiftCardClaimed($giftCard, $claimant);
    }
}
