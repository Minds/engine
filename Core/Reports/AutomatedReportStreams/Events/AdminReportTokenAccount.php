<?php
namespace Minds\Core\Reports\AutomatedReportStreams\Events;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;

class AdminReportTokenAccountEvent implements EventInterface
{
    use TimebasedEventTrait;

    protected string $userGuid;
    protected string $userType;
    protected int $engagementCount;
    protected float $avgEngagementScore;
    protected int $contentCount;
    protected float $avgContentScore;
    protected float $score;

    public function __construct(array $data)
    {
        // {'name': 'user_guid', 'type': 'long' },
        // {'name': 'time_created', 'type': 'long' },
        // {'name': 'user_type', 'type': ['null', 'string' ] },
        // {'name': 'engagement_count', 'type': ['null', 'int' ] },
        // {'name': 'avg_engagement_score', 'type': ['null', 'double' ] },
        // {'name': 'content_count', 'type': ['null', 'int' ] },
        // {'name': 'avg_content_score', 'type': ['null', 'double' ] },
        // {'name': 'score', 'type': ['null', 'double' ] }

        $this->userGuid = $data['user_guid'];
        $this->userType = $data['user_type'];
        $this->engagementCount = $data['engagement_count'];
        $this->avgEngagementScore = $data['avg_engagement_score'];
        $this->contentCount = $data['content_count'];
        $this->avgContentScore = $data['avg_content_score'];
        $this->score = $data['score'];
    }

    /**
     * @return string
     */
    public function getUserGuid(): string
    {
        return $this->userGuid;
    }

    /**
     * @return string
     */
    public function getUserType(): string
    {
        return $this->userType;
    }

    /**
     * @return int
     */
    public function getEngagementCount(): int
    {
        return $this->engagementCount;
    }

    /**
     * @return float
     */
    public function getAvgEngagementScore(): float
    {
        return $this->avgEngagementScore;
    }

    /**
     * @return int
     */
    public function getContentCount(): int
    {
        return $this->contentCount;
    }

    /**
     * @return float
     */
    public function getAvgContentScore(): float
    {
        return $this->avgContentScore;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }
}
