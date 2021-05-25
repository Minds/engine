<?php
namespace Minds\Core\EventStreams;

class ActionEvent implements EventInterface
{
    use EntityEventTrait;

    /** @var string */
    const ACTION_VOTE_UP = 'vote_up';

    /** @var string */
    const ACTION_VOTE_DOWN = 'vote_down';

    /** @var string */
    const ACTION_COMMENT = 'comment';

    /** @var string */
    const ACTION_QUOTE = 'quote';

    /** @var string */
    const ACTION_REMIND = 'remind';

    /** @var string */
    const ACTION_SUBSCRIBE = 'subscribe';

    /** @var string */
    const ACTION_REFERRAL_PING = 'referral_ping';

    /** @var string */
    const ACTION_REFERRAL_PENDING = 'referral_pending';

    /** @var string */
    const ACTION_REFERRAL_COMPLETE = 'referral_complete';

    /** @var string */
    const ACTION_TAG = 'tag';

    /** @var string */
    const ACTION_BOOST_REJECTED = 'boost_rejected';

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
    const ACTION_GROUP_QUEUE_APPROVE = 'group_queue_approve';

    /** @var string */
    const ACTION_GROUP_QUEUE_REJECT = 'group_queue_reject';

    /** @var string */
    const ACTION_WIRE_SENT = 'wire_sent';

    /** @var string */
    const ACTION_BLOCK = 'block';

    /** @var string */
    const ACTION_UNBLOCK = 'unblock';

    /** @var string */
    protected $action;

    /** @var string[] */
    protected $actionData = [];

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
                $allowedKeys = [ 'quote_urn' ];
                break;
            case self::ACTION_REFERRAL_PING:
            case self::ACTION_REFERRAL_PENDING:
            case self::ACTION_REFERRAL_COMPLETE:
                break;
            case self::ACTION_SUBSCRIBE:
                break;
            case self::ACTION_TAG:
                $allowedKeys = [ 'tag_in_entity_urn' ];
                break;
            case self::ACTION_BOOST_REJECTED:
                $allowedKeys = [ 'reason' ];
                break;
            case self::ACTION_BOOST_PEER_REQUEST:
            case self::ACTION_BOOST_PEER_ACCEPTED:
            case self::ACTION_BOOST_PEER_REJECTED:
                // toGuid is only used for boost_peer_request
                $allowedKeys = [ 'bid', 'type', 'toGuid'];
                break;
            case self::ACTION_TOKEN_WITHDRAW_ACCEPTED:
            case self::ACTION_TOKEN_WITHDRAW_REJECTED:
                $allowedKeys = [ 'amount' ];
                break;
            case self::ACTION_GROUP_INVITE:
            case self::ACTION_GROUP_QUEUE_ADD:
            case self::ACTION_GROUP_QUEUE_APPROVE:
            case self::ACTION_GROUP_QUEUE_REJECT:
                $allowedKeys = [ 'group_urn' ];
                break;
           case self::ACTION_WIRE_SENT:
                $allowedKeys = [ 'amount' ];
                break;
            case self::ACTION_BLOCK:
            case self::ACTION_UNBLOCK:
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
}
