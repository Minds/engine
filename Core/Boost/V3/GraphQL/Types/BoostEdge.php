<?php
namespace Minds\Core\Boost\V3\GraphQL\Types;

use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\GraphQL\Types\EdgeInterface;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * The BoostEdge hold the BoostNode and cursor information.
 * Other relevant information, such as relationships, can also be included in the Edge.
 */
#[Type]
class BoostEdge implements EdgeInterface
{
    protected Boost $boost;

    public function __construct(Boost $boost)
    {
        $this->boost = $boost;
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("boost-" . $this->boost->getGuid());
    }

    #[Field]
    public function getType(): string
    {
        return "boost";
    }

    #[Field]
    public function getNode(): BoostNode
    {
        return new BoostNode($this->boost);
    }

    #[Field]
    public function getCursor(): string
    {
        return '';
    }
}
