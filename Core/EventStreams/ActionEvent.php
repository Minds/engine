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
    const ACTION_REMIND = 'remind';

    /** @var string */
    const ACTION_SUBSCRIBE = 'subscribe';

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
                $allowedKeys = [ 'remind_guid' ];
                break;
            case self::ACTION_SUBSCRIBE:
                break;
            case self::ACTION_TAG:
                // Should tag be entity_guid for the tagged person or entity_guid be the
                // post that contains the tag
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
