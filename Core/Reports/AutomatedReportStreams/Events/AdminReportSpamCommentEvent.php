<?php
namespace Minds\Core\Reports\AutomatedReportStreams\Events;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;

class AdminReportSpamCommentEvent implements EventInterface
{
    use TimebasedEventTrait;

    protected string $commentUrn;
    protected string $ownerGuid;
    protected float $spamPredict;
    protected int $activityViews;
    protected int $lastEngagement;
    protected float $score;

    public function __construct(array $data)
    {
        // {'name': 'comment_guid', 'type': 'long' },
        // {'name': 'owner_guid', 'type': 'long' },
        // {'name': 'entity_guid', 'type': ['null', 'long' ] },
        // {'name': 'parent_guid_l1', 'type': ['null', 'long' ] },
        // {'name': 'parent_guid_l2', 'type': ['null', 'long' ] },
        // {'name': 'parent_guid_l3', 'type': ['null', 'long' ] },
        // {'name': 'time_created', 'type': 'long' },
        // {'name': 'spam_predict', 'type': 'double' },
        // {'name': 'activity_views', 'type': 'int' },
        // {'name': 'last_engagement', 'type': 'long' },
        // {'name': 'score', 'type': 'double' }

        $this->commentUrn = 'urn:comment:' . implode(':', [
            $data['entity_guid'],
            $data['parent_guid_l1'],
            $data['parent_guid_l2'],
            $data['parent_guid_l3'],
            $data['comment_guid']
        ]);
        $this->ownerGuid = $data['owner_guid'];
        $this->spamPredict = $data['spam_predict'];
        $this->activityViews = $data['activity_views'];
        $this->lastEngagement = $data['last_engagement'];
        $this->score = $data['score'];
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
    public function getSpamPredict(): float
    {
        return $this->spamPredict;
    }

    /**
     * @return int
     */
    public function getActivityViews(): int
    {
        return $this->activityViews;
    }

    /**
     * @return int
     */
    public function getLastEngagement(): int
    {
        return $this->lastEngagement;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }
}
