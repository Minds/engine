<?php
namespace Minds\Core\Search\Types;

use Minds\Core\GraphQL\Types\Connection;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class SearchResultsCount
{
    #[Field]
    public function getCount(): int
    {
        return 0;
    }
}
