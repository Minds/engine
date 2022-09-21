<?php
namespace Minds\Core\Reports\AutomatedReportStreams\Events;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;

class ScoreCommentsForSpamEvent implements EventInterface
{
    use TimebasedEventTrait;

    /** @var string */
    const TOPIC_NAME = 'score-comments-for-spam-json';

    protected string $commentUrn;
    protected string $ownerGuid;
    protected float $spamScore;

    public function __construct(array $data)
    {
        // {'name': 'comment_guid', 'type': 'long' },
        // {'name': 'owner_guid', 'type': 'long' },
        // {'name': 'entity_guid', 'type': ['null', 'long' ] },
        // {'name': 'parent_guid_l1', 'type': ['null', 'long' ] },
        // {'name': 'parent_guid_l2', 'type': ['null', 'long' ] },
        // {'name': 'parent_guid_l3', 'type': ['null', 'long' ] },
        // {'name': 'time_created', 'type': 'long' },
        // {'name': 'spam_score', 'type': 'double' }

        $this->commentUrn = 'urn:comment:' . implode(':', [
            $data['entity_guid'],
            $data['parent_guid_l1'] ?? 0,
            $data['parent_guid_l2'] ?? 0,
            $data['parent_guid_l3'] ?? 0,
            $data['comment_guid']
        ]);
        $this->ownerGuid = $data['owner_guid'];
        $this->spamScore = $data['spam_score'];
    }

    /**
     * @return string
     */
    public function getCommentUrn(): string
    {
        return $this->commentUrn;
    }

    /**
     * @return string
     */
    public function getOwnerGuid(): string
    {
        return $this->ownerGuid;
    }

    /**
     * @return float
     */
    public function getSpamScore(): float
    {
        return $this->spamScore;
    }
}
