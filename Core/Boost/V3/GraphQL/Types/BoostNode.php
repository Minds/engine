<?php
namespace Minds\Core\Boost\V3\GraphQL\Types;

use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Models\BoostEntityWrapper;
use Minds\Core\Feeds\GraphQL\Types\ActivityNode;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Session;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * BoostNode to be exposed in graphql.
 * This is a temporary solution until the clients can better abstract the 'activity' data
 * from the boost.
 */
#[Type]
class BoostNode implements NodeInterface
{
    public function __construct(
        protected Boost $boost,
        protected ?User $loggedInUser = null,
    ) {
        $this->entity = $boost;  // Pass 'entity' through to abstract lower layer
        $this->loggedInUser ??= Session::getLoggedinUser();
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("boost-" . $this->boost->getGuid());
    }

    #[Field]
    public function getGuid(): string
    {
        return $this->boost->getGuid();
    }

    /**
     * @return string
     */
    #[Field]
    public function getLegacy(): string
    {
        $wrappedBoost = new BoostEntityWrapper($this->boost);
        return json_encode($wrappedBoost->export(), JSON_UNESCAPED_SLASHES);
    }

    #[Field]
    public function getActivity(): ActivityNode
    {
        return new ActivityNode($this->boost->getEntity());
    }

    #[Field]
    public function getGoalButtonText(): ?int
    {
        return $this->boost->getGoalButtonText();
    }

    #[Field]
    public function getGoalButtonUrl(): ?string
    {
        return $this->boost->getGoalButtonUrl();
    }
}
