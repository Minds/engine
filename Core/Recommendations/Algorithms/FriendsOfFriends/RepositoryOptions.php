<?php

namespace Minds\Core\Recommendations\Algorithms\FriendsOfFriends;

use Minds\Entities\ValidationError;
use Minds\Traits\MagicAttributes;

/**
 * Represents the options for a recommendations repository
 * @method int getLimit()
 * @method int setLimit(int $limit)
 * @method string getTargetUserGuid()
 * @method string setTargetUserGuid(string $targetUserGuid)
 * @method string getMostRecentSubscriptionUserGuid()
 * @method string setMostRecentSubscriptionUserGuid(string $targetUserGuid)
 */
class RepositoryOptions
{
    use MagicAttributes;

    /**
     * @type int
     */
    private const HARD_LIMIT = 150;

    /**
     * @var int Maximum amount of recommendations to be returned
     */
    private int $limit = 12;

    /**
     * @var string Target User guid for the base use to fetch subscriptions of
     */
    private string $targetUserGuid;

    /**
     * @var string|null User guid of the latest subscription made by the target user
     */
    private ?string $mostRecentSubscriptionUserGuid = null;

    public function __construct(array $options = [])
    {
        $this->init($options);
    }

    /**
     * Initialise class properties using the provided array of options
     * @param array $options
     * @return $this
     */
    public function init(array $options): self
    {
        foreach ($options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
