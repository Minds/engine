<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Delegates;

use Exception;
use Minds\Common\SystemUser;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Types\GiftCardTarget;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class NotificationDelegate
{
    public function __construct(
        private readonly ActionEventsTopic  $actionEventsTopic,
        private readonly EntitiesBuilder    $entitiesBuilder,
        private readonly ExperimentsManager $experimentsManager,
        private readonly Logger             $logger
    ) {
    }

    /**
     * Generates a new notification event for the gift card recipient
     * @param GiftCard $giftCard
     * @param GiftCardTarget $recipient
     * @return void
     * @throws GraphQLException
     * @throws Exception
     */
    public function onCreateGiftCard(
        GiftCard $giftCard,
        GiftCardTarget $recipient,
    ): void {
        if (!$this->experimentsManager->isOn('minds-4126-gift-card-claim')) {
            return;
        }

        if (!$recipient->targetUserGuid) {
            $this->logger->warning('Gift card recipient notification event not sent. Target user guid not provided.', [
                'gift_card_guid' => $giftCard->guid,
            ]);
            return;
        }

        $recipientUser = $this->entitiesBuilder->single($recipient->targetUserGuid) ?? throw new GraphQLException("Recipient user not found", 400, null, 'Validation', ['field' => 'targetInput']);

        $this->actionEventsTopic->send(
            (new ActionEvent())
                ->setUser(new SystemUser())
                ->setAction(ActionEvent::ACTION_GIFT_CARD_RECIPIENT_NOTIFICATION)
                ->setEntity($recipientUser)
                ->setActionData([
                    'gift_card_guid' => $giftCard->guid,
                    'sender_guid' => $giftCard->issuedByGuid,
                ])
        );
        $this->logger->info('Gift card recipient notification event sent.', [
            'gift_card_guid' => $giftCard->guid,
            'recipient_user_guid' => $recipientUser->getGuid(),
        ]);
    }
}
