<?php
namespace Minds\Core\Reports\AutomatedReportStreams\Events;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;

class AdminReportSpamAccountEvent implements EventInterface
{
    use TimebasedEventTrait;

    /** @var string */
    const TOPIC_NAME = 'admin-report-spam-accounts-json';

    protected string $userGuid;
    protected int $totalComments;
    protected int $spamComments;
    protected int $spamCommentsWithLink;
    protected float $spamPercent;
    protected float $linkPercent;
    protected int $uniqueSpamLinks;
    protected int $secsSinceMostRecentSpamComment;
    protected float $score;

    public function __construct(array $data)
    {
        // {'name': 'user_guid', 'type': 'long' },
        // {'name': 'total_comments', 'type': ['null', 'int' ] },
        // {'name': 'spam_comments', 'type': ['null', 'int' ] },
        // {'name': 'spam_comments_with_link', 'type': ['null', 'int' ] },
        // {'name': 'spam_percent', 'type': ['null', 'double' ] },
        // {'name': 'link_percent', 'type': ['null', 'double' ] },
        // {'name': 'unique_spam_links', 'type': ['null', 'int' ] },
        // {'name': 'secs_since_most_recent_spam_comment', 'type': ['null', 'int' ] },
        // {'name': 'score', 'type': 'double' }

        $this->userGuid = $data['user_guid'];
        $this->totalComments = $data['total_comments'];
        $this->spamComments = $data['spam_comments'];
        $this->spamCommentsWithLink = $data['spam_comments_with_link'];
        $this->spamPercent = $data['spam_percent'];
        $this->linkPercent = $data['link_percent'];
        $this->uniqueSpamLinks = $data['unique_spam_links'];
        $this->secsSinceMostRecentSpamComment = $data['secs_since_most_recent_spam_comment'];
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
     * @return int
     */
    public function getTotalComments(): int
    {
        return $this->totalComments;
    }

    /**
     * @return int
     */
    public function getSpamComments(): int
    {
        return $this->spamComments;
    }

    /**
     * @return int
     */
    public function getSpamCommentsWithLink(): int
    {
        return $this->spamCommentsWithLink;
    }

    /**
     * @return float
     */
    public function getSpamPercent(): float
    {
        return $this->spamPercent;
    }

    /**
     * @return float
     */
    public function getLinkPercent(): float
    {
        return $this->linkPercent;
    }

    /**
     * @return int
     */
    public function getUniqueSpamLinks(): int
    {
        return $this->uniqueSpamLinks;
    }

    /**
     * @return int
     */
    public function getSecsSinceMostRecentSpamComment(): int
    {
        return $this->secsSinceMostRecentSpamComment;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }
}
