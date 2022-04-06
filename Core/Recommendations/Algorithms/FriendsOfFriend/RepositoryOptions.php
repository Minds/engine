<?php

namespace Minds\Core\Recommendations\Algorithms\FriendsOfFriend;

use Minds\Traits\MagicAttributes;

/**
 * Represents the options for a recommendations repository
 * @method int getLimit()
 * @method self setLimit(int $limit)
 * @method string getTargetUserGuid()
 * @method self setTargetUserGuid(string $targetUserGuid)
 * @method string getCurrentChannelUserGuid()
 * @method self setCurrentChannelUserGuid(string $currentChannelUserGuid)
 * @method array getMostRecentSubscriptions()
 * @method self setMostRecentSubscriptions(string[] $mostRecentSubscriptions)
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
     * @var string|null User guid of the channel currently visited by the target user
     */
    private ?string $currentChannelUserGuid = null;

    /**
     * @var string[]|null User guids of the most recent subscriptions made by the target user
     */
    private ?array $mostRecentSubscriptions = null;

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
