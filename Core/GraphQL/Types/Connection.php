<?php
namespace Minds\Core\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * Connection type, following the Cursor Connection Spec (https://relay.dev/graphql/connections.htm)
 */
#[Type]
class Connection
{
    /** @var EdgeInterface[] */
    protected array $edges = [];

    protected PageInfo $pageInfo;

    /**
     * @return EdgeInterface[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }

    public function setEdges(array $edges): self
    {
        $this->edges = $edges;
        return $this;
    }

    #[Field]
    public function getPageInfo(): PageInfo
    {
        return $this->pageInfo;
    }

    public function setPageInfo(PageInfo $pageInfo): self
    {
        $this->pageInfo = $pageInfo;
        return $this;
    }
}
