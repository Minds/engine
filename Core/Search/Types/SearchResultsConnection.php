<?php
namespace Minds\Core\Search\Types;

use Minds\Core\GraphQL\Types\Connection;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class SearchResultsConnection extends Connection
{
    private int $count = 0;

    /**
     * The number of search records matching the query
     */
    #[Field]
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Set the value of the count
     */
    public function setCount(int $count): self
    {
        $this->count = $count;
        return $this;
    }
}
