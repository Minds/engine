<?php
namespace Minds\Core\EventStreams;

use Minds\Core\Entity;
use Minds\Entities\User;

/**
 * Event for admin actions.
 */
class AdminActionEvent implements EventInterface
{
    use EntityEventTrait;

    /** @var string nsfw_lock change action */
    public const ACTION_NSFW_LOCK = 'nsfw_lock';

    
    /** @var Entity|User subject of admin action */
    protected $subject = null;
    
    /** @var User admin actor */
    protected $actor = null;

    /**
     * Sets the action of the event.
     * @param string $action - action to set to.
     * @return self
     */
    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Gets the event action.
     * @return string the event's action.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Sets action data
     * @param array $actionData - key value array of action data.
     * @return self
     */
    public function setActionData(array $actionData): self
    {
        $allowedKeys = [];

        // set allowed keys for this action.
        switch ($this->action) {
            case self::ACTION_NSFW_LOCK:
                $allowedKeys = ['nsfw_lock'];
                break;
            default:
                throw new \Exception("Invalid action set. Ensure allowedKeys are set in AdminActionEvent model");
        }

        // iterate through keys to ensure only permitted keys are included.
        foreach (array_keys($actionData) as $key) {
            if (!in_array($key, $allowedKeys, true)) {
                throw new \Exception("actionData set keys we are not expecting. Ensure allowedKeys are set in AdminActionEvent model");
            }
        }

        $this->actionData = $actionData;
        return $this;
    }

    /**
     * Action data array.
     * @return array - key value array of action data
     */
    public function getActionData(): array
    {
        return $this->actionData;
    }

    /**
     * The event timestamp.
     * @param int $timestamp - timestamp of event.
     * @return self
     */
    public function setTimestamp(int $timestamp): EventInterface
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * The timestamp of the event
     * @return int - timestamp of the event.
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Get the actor of the event - the admin.
     * @return User The actor of event - the admin.
     */
    public function getActor(): User
    {
        return $this->actor;
    }

    /**
     * Set the actor of the event - the admin.
     * @param User $actor - The actor of event - the admin.
     * @return self
     */
    public function setActor(User $actor): self
    {
        $this->actor = $actor;
        return $this;
    }

    /**
     * Get the subject of the event - the entity or user.
     * @return Entity|User The subject of the event - the entity or user.
     */
    public function getSubject(): Entity|User
    {
        return $this->subject;
    }

    /**
     * Set the subject of the event - the entity or user.
     * @param User $actor - The subject of the event - the entity or user.
     * @return self
     */
    public function setSubject(Entity|User $subject): EventInterface
    {
        $this->subject = $subject;
        return $this;
    }
}
