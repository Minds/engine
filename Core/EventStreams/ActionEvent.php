<?php
namespace Minds\Core\EventStreams;

class ActionEvent implements EventInterface
{
    use EntityEventTrait;

    /** @var string */
    const ACTION_VOTE = 'vote';

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
    const ACTION_TAG = 'tag';

    /** @var string */
    const ACTION_BLOCK = 'block';

    /** @var string */
    const ACTION_UNBLOCK = 'unblock';

    /** @var string */
    protected $action;

    /** @var string[] */
    protected $actionData = [];

    /** @var int */
    protected $timestamp = 0;

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
            case self::ACTION_VOTE:
                $allowedKeys = [ 'vote_direction' ];
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
            case self::ACTION_SUBSCRIBE:
            case self::ACTION_UNSUBSCRIBE:
                break;
            case self::ACTION_TAG:
                $allowedKeys = [ 'tag_in_entity_urn' ];
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

    /**
     * The event timestamp
     * @param int $timestamp
     * @return self
     */
    public function setTimestamp(int $timestamp): EventInterface
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}
