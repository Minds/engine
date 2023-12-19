<?php
namespace Minds\Core\Comments\EmbeddedComments\Types;

use Minds\Core\Comments\GraphQL\Types\CommentEdge;
use Minds\Core\GraphQL\Types\Connection;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class EmbeddedCommentsConnection extends Connection
{
    private int $count = 0;

    private string $activityUrl;

    /**
     * The number of comments found
     */
    #[Field]
    public function getTotalCount(): int
    {
        return $this->count;
    }

    /**
     * Set the value of the count
     */
    public function setTotalCount(int $count): self
    {
        $this->count = $count;
        return $this;
    }

    /**
     * The url of the activity post
     */
    #[Field]
    public function getActivityUrl(): string
    {
        return $this->activityUrl;
    }

    /**
     * Set the value of the activity url
     */
    public function setActivityUrl(string $url): self
    {
        $this->activityUrl = $url;
        return $this;
    }

    /**
     * @return CommentEdge[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
