<?php

namespace Minds\Core\Governance\Entities;

use Cassandra\Timestamp;
use Cassandra\Collection;
use Minds\Traits\MagicAttributes;

/**
 * Class ProposalModel
 * @package Minds\Core\Governance\Entities
 * @method ProposalModel setTitle(string $value)
 * @method ProposalModel getTitle()
 * 
 * @method ProposalModel setProposalId(string $value)
 * @method ProposalModel getProposalId()

 * @method ProposalModel setBody(string $value)
 * @method ProposalModel getBody()

 * @method ProposalModel setChoices(Collection $value)
 * @method ProposalModel getChoices()

 * @method ProposalModel setType(string $value)
 * @method ProposalModel getType()

 * @method ProposalModel setCategory(string $value)
 * @method ProposalModel getCategory()

 * @method ProposalModel setAuthor(string $value)
 *  @method ProposalModel getAuthor()

 * @method ProposalModel setTimeCreated(Timestamp $value)
 * @method ProposalModel getTimeCreated()

 * @method ProposalModel setTimeEnd(Timestamp $value)
 * @method ProposalModel getTimeEnd()
 * @method ProposalModel setSnapshotId(string $value)
 * @method ProposalModel getSnapshotId()
 
 * @method ProposalModel setState(string $value)
 * @method ProposalModel getState()

 * @method ProposalModel setMetadata(string $value)
 * @method ProposalModel getMetadata()

 */

class ProposalModel
{
    use MagicAttributes;

    /** @var string */
    protected $proposalId;

    /** @var string */
    protected $title;
    protected $body;
    protected $choices;
    protected $type;
    protected $category;
    protected $author;
    protected $timeCreated;
    protected $timeEnd;
    protected $snapshotId;
    protected $state;
    protected $metadata;

    /**
     * Set proposalId
     * @param string $proposalId
     * @return $this
     */
    public function setProposalId($proposalId)
    {
        $this->proposalId = $proposalId;
        return $this;
    }

    /**
     * Get proposalId
     * @return $this
     */
    public function getProposalId(): string
    {
        return $this->proposalId;
    }

    /**
     * Set title
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     * @return $this
     */
    public function getTitle(): string
    {
        return $this->title;
    }


    /**
     * Set body
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Get body
     * @return $this
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set choices
     * @param Collection $choices
     * @return $this
     */
    public function setChoices($choices)
    {
        $this->choices = $choices;
        return $this;
    }

    /**
     * Get choices
     * @return $this
     */
    public function getChoices(): Collection
    {
        return $this->choices;
    }

    /**
     * Set type
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     * @return $this
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set category
     * @param string $category
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Get category
     * @return $this
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Set author
     * @param string $author
     * @return $this
     */
    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Get author
     * @return $this
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Set time_created
     * @param Timestamp $time_created
     * @return $this
     */
    public function setTimeCreated($timeCreated)
    {
        $this->timeCreated = $timeCreated;
        return $this;
    }

    /**
     * Get timeCreated
     * @return $this
     */
    public function getTimeCreated(): Timestamp
    {
        return $this->timeCreated;
    }

    /**
     * Set time_end
     * @param Timestamp $time_end
     * @return $this
     */
    public function setTimeEnd($timeEnd)
    {
        $this->timeEnd = $timeEnd;
        return $this;
    }

    /**
     * Get timeEnd
     * @return $this
     */
    public function getTimeEnd(): Timestamp
    {
        return $this->timeEnd;
    }

    /**
     * Set snapshot_id
     * @param string $snapshot_id
     * @return $this
     */
    public function setSnapshotId($snapshotId)
    {
        $this->snapshotId = $snapshotId;
        return $this;
    }

    /**
     * Get snapshot_id
     * @return $this
     */
    public function getSnapshotId(): Timestamp
    {
        return $this->snapshotId;
    }

    /**
     * Set state
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Get state
     * @return $this
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Set metadata
     * @param string $metadata
     * @return $this
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Get metadata
     * @return $this
     */
    public function getMetadata(): string
    {
        return $this->metadata;
    }

    /**
     * Export the item to an array
     * @return array
     */
    public function export()
    {
        return [
            "proposalId" => $this->proposalId,
            "title" => $this->title,
            "body" => $this->body,
            "choices" => $this->choices,
            "type" => $this->type,
            "category" => $this->category,
            "author" => $this->author,
            "timeCreated" => $this->timeCreated,
            "timeEnd" => $this->timeEnd,
            "snapshotId" => $this->snapshotId,
            "state" => $this->state,
            "metadata" => $this->metadata
        ];
    }
}
