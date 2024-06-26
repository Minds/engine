<?php
namespace Minds\Core\EventStreams;

class ActionEvent implements EventInterface
{
    use EntityEventTrait;
    use AcknowledgmentEventTrait;
    use TimebasedEventTrait;

    /** @var string */
    const ACTION_CREATE = 'create';

    /** @var string */
    const ACTION_VOTE_UP = 'vote_up';

    /** @var string */
    const ACTION_VOTE_UP_REMOVED = 'vote_up_removed';

    /** @var string */
    const ACTION_VOTE_DOWN = 'vote_down';

    /** @var string */
    const ACTION_VOTE_DOWN_REMOVED = 'vote_down_removed';

    /** @var string */
    const ACTION_COMMENT = 'comment';

    /** @var string */
    const ACTION_QUOTE = 'quote';

    /** @var string */
    const ACTION_REMIND = 'remind';

    /** @var string */
    const ACTION_SUBSCRIBE = 'subscribe';

    /** @var string */
    const ACTION_UNSUBSCRIBE = 'unsubscribe';

    /** @var string */
    const ACTION_REFERRAL_PING = 'referral_ping';

    /** @var string */
    const ACTION_REFERRAL_PENDING = 'referral_pending';

    /** @var string */
    const ACTION_REFERRAL_COMPLETE = 'referral_complete';

    /** @var string */
    const ACTION_TAG = 'tag';

    /** @var string */
    const ACTION_BOOST_CREATED = 'boost_created';

    /** @var string */
    const ACTION_BOOST_REJECTED = 'boost_rejected';

    /** @var string */
    const ACTION_BOOST_ACCEPTED = 'boost_accepted';

    /** @var string */
    const ACTION_BOOST_COMPLETED = 'boost_completed';

    /** @var string */
    const ACTION_BOOST_CANCELLED = 'boost_cancelled';

    /** @var string */
    const ACTION_BOOST_PEER_REQUEST = 'boost_peer_request';

    /** @var string */
    const ACTION_BOOST_PEER_ACCEPTED = 'boost_peer_accepted';

    /** @var string */
    const ACTION_BOOST_PEER_REJECTED = 'boost_peer_rejected';

    /** @var string */
    const ACTION_TOKEN_WITHDRAW_ACCEPTED = 'token_withdraw_accepted';

    /** @var string */
    const ACTION_TOKEN_WITHDRAW_REJECTED = 'token_withdraw_rejected';

    /** @var string */
    const ACTION_GROUP_INVITE = 'group_invite';

    /** @var string */
    const ACTION_GROUP_QUEUE_ADD = 'group_queue_add';

    /** @var string */
    const ACTION_GROUP_QUEUE_RECEIVED = 'group_queue_received';
    
    /** @var string */
    const ACTION_GROUP_QUEUE_APPROVE = 'group_queue_approve';

    /** @var string */
    const ACTION_GROUP_QUEUE_REJECT = 'group_queue_reject';

    /** @var string */
    const ACTION_WIRE_SENT = 'wire_sent';

    /** @var string */
    const ACTION_BLOCK = 'block';

    /** @var string */
    const ACTION_UNBLOCK = 'unblock';

    /** @var array */
    const ACTION_NSFW_LOCK = 'nsfw_lock';

    /** @var string */
    const ACTION_SYSTEM_PUSH_NOTIFICATION = 'system_push_notification';

    /** @var string */
    const ACTION_SUPERMIND_REQUEST_CREATE = 'supermind_request_create';

    /** @var string */
    const ACTION_SUPERMIND_REQUEST_ACCEPT = 'supermind_request_accept';

    /** @var string */
    const ACTION_SUPERMIND_REQUEST_REJECT = 'supermind_request_reject';

    /** @var string */
    const ACTION_SUPERMIND_REQUEST_EXPIRE = 'supermind_request_expire';

    /** @var string */
    const ACTION_SUPERMIND_REQUEST_EXPIRING_SOON = 'supermind_request_expiring_soon';

    /** @var string */
    const ACTION_USER_VERIFICATION_PUSH_NOTIFICATION = 'user_verification_push_notification';

    /** @var string */
    const ACTION_CLICK = 'click';

    public const ACTION_AFFILIATE_EARNINGS_DEPOSITED = "affiliate_earnings_deposited";
    public const ACTION_REFERRER_AFFILIATE_EARNINGS_DEPOSITED = "referrer_affiliate_earnings_deposited";

    public const ACTION_GIFT_CARD_RECIPIENT_NOTIFICATION = "gift_card_recipient_notification";
    public const ACTION_GIFT_CARD_ISSUER_CLAIMED_NOTIFICATION = "gift_card_issuer_claimed_notification";

    public const ACTION_UPHELD_REPORT = "upheld_report";

    /** @var string */
    protected $action;

    /** @var string[] */
    protected $actionData = [];

    protected int $delayMs = 0;

    /**
     * @param string $action
     * @return self
     */
    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
    * @param string[] $actionData
    * @return self
    */
    public function setActionData(array $actionData): self
    {
        /** @var string[] */
        $allowedKeys = [];

        switch ($this->action) {
            case self::ACTION_CREATE:
                break;
            case self::ACTION_VOTE_UP_REMOVED:
            case self::ACTION_VOTE_DOWN_REMOVED:
            case self::ACTION_VOTE_UP:
            case self::ACTION_VOTE_DOWN:
                break;
            case self::ACTION_COMMENT:
                $allowedKeys = [ 'comment_urn' ];
                break;
            case self::ACTION_REMIND:
                $allowedKeys = [ 'remind_urn' ];
                break;
            case self::ACTION_QUOTE:
                $allowedKeys = [ 'quote_urn', 'is_supermind_reply' ];
                break;
            case self::ACTION_REFERRAL_PING:
            case self::ACTION_REFERRAL_PENDING:
            case self::ACTION_REFERRAL_COMPLETE:
                break;
            case self::ACTION_SUBSCRIBE:
            case self::ACTION_UNSUBSCRIBE:
                break;
            case self::ACTION_TAG:
                $allowedKeys = [ 'tag_in_entity_urn' ];
                break;
            case self::ACTION_BOOST_REJECTED:
                $allowedKeys = [ 'boost_reject_reason' ];
                break;
            case self::ACTION_BOOST_CREATED:
            case self::ACTION_BOOST_ACCEPTED:
            case self::ACTION_BOOST_COMPLETED:
            case self::ACTION_BOOST_CANCELLED:
                break;
            case self::ACTION_BOOST_PEER_REQUEST:
            case self::ACTION_BOOST_PEER_ACCEPTED:
            case self::ACTION_BOOST_PEER_REJECTED:
                break;
            case self::ACTION_TOKEN_WITHDRAW_ACCEPTED:
            case self::ACTION_TOKEN_WITHDRAW_REJECTED:
                break;
            case self::ACTION_GROUP_INVITE:
            case self::ACTION_GROUP_QUEUE_ADD:
            case self::ACTION_GROUP_QUEUE_RECEIVED:
            case self::ACTION_GROUP_QUEUE_APPROVE:
            case self::ACTION_GROUP_QUEUE_REJECT:
                $allowedKeys = [ 'group_urn' ];
                break;
            case self::ACTION_WIRE_SENT:
                $allowedKeys = [ 'wire_amount' ];
                break;
            case self::ACTION_BLOCK:
            case self::ACTION_UNBLOCK:
                break;
            case self::ACTION_NSFW_LOCK:
                $allowedKeys = [ 'nsfw_lock' ];
                break;
            case self::ACTION_SYSTEM_PUSH_NOTIFICATION:
                break;
            case self::ACTION_SUPERMIND_REQUEST_CREATE:
            case self::ACTION_SUPERMIND_REQUEST_ACCEPT:
            case self::ACTION_SUPERMIND_REQUEST_REJECT:
            case self::ACTION_SUPERMIND_REQUEST_EXPIRING_SOON:
            case self::ACTION_SUPERMIND_REQUEST_EXPIRE:
                break;
            case self::ACTION_USER_VERIFICATION_PUSH_NOTIFICATION:
                break;
            case self::ACTION_CLICK:
                break;
            case self::ACTION_AFFILIATE_EARNINGS_DEPOSITED:
            case self::ACTION_REFERRER_AFFILIATE_EARNINGS_DEPOSITED:
                $allowedKeys = [
                    'user_guid',
                    'timestamp',
                    'item',
                    'amount_cents',
                    'amount_usd',
                    'amount_tokens'
                ];
                break;
            case self::ACTION_GIFT_CARD_RECIPIENT_NOTIFICATION:
                $allowedKeys = [
                    'gift_card_guid',
                    'sender_guid',
                ];
                break;
            case self::ACTION_GIFT_CARD_ISSUER_CLAIMED_NOTIFICATION:
                $allowedKeys = [
                    'gift_card_guid',
                    'claimant_guid'
                ];
                break;
            case self::ACTION_UPHELD_REPORT:
                break;
            default:
                throw new \Exception("Invalid action set. Ensure allowedKeys are set in ActionEvent model");
        }

        foreach (array_keys($actionData) as $key) {
            if (!in_array($key, $allowedKeys, true)) {
                throw new \Exception("actionData set keys we are not expecting. Ensure allowedKeys are set in ActionEvent model");
            }
        }

        $this->actionData = $actionData;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getActionData(): array
    {
        return $this->actionData;
    }

    /**
     * Set if the action should be delayed or not
     */
    public function setDelayMs(int $ms): self
    {
        $this->delayMs = $ms;
        return $this;
    }

    /**
     * Returns the intended delay, in milliseconds
     */
    public function getDelayMs(): int
    {
        return $this->delayMs;
    }
}
