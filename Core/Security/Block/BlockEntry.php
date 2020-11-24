<?php
namespace Minds\Core\Security\Block;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

class BlockEntry
{
    use MagicAttributes;

    /** @var string */
    protected $actorGuid;

    /** @var */
    protected $subjectGuid;

    /**
     * Helper function to set the actorGuid from an entity
     * @param mixed $actor
     * @return self
     */
    public function setActor($actor): self
    {
        if ($actor instanceof User) {
            $this->actorGuid = (string) $actor->getGuid();
        } else {
            // If this isn't a user, then assume its a standard entity
            $this->actorGuid = (string) $actor->owner_guid;
        }

        return $this;
    }

    /**
     * Helper function to set the subjectGuid from an entity
     * @param mixed $subject
     * @return self
     */
    public function setSubject($subject): self
    {
        if ($subject instanceof User) {
            $this->subjectGuid = (string) $subject->getGuid();
        } else {
            // If this isn't a user, then assume its a standard entity
            $this->subjectGuid = (string) $subject->owner_guid;
        }

        return $this;
    }
}
