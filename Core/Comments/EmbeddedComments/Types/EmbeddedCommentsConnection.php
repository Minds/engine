<?php
namespace Minds\Core\Comments\EmbeddedComments\Types;

use Minds\Core\Comments\GraphQL\Types\CommentEdge;
use Minds\Core\GraphQL\Types\Connection;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class EmbeddedCommentsConnection extends Connection
{
    /**
     * @return CommentEdge[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
