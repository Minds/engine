<?php
/**
 * All entities that support comments should implement this interface
 */
namespace Minds\Entities;

interface CommentableEntityInterface
{
    /**
     * True/False, if comments are allowed on this entity
     */
    public function getAllowComments(): bool;

    /**
     * Sets the entity to be in an enabled or disabled commenting state
     */
    public function setAllowComments(bool $allowComments): self;
}
