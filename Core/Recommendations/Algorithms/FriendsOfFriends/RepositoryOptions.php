<?php

namespace Minds\Core\Recommendations\Algorithms\FriendsOfFriends;

use Minds\Entities\ValidatableObjectInterface;
use Minds\Entities\ValidationError;
use Minds\Traits\MagicAttributes;

/**
 * Represents the options for a recommendations repository
 * @method int getLimit()
 * @method int setLimit(int $limit)
 * @method int getOffset()
 * @method int setOffset(int $offset)
 * @method string getTargetUserGuid()
 * @method string setTargetUserGuid(string $targetUserGuid)
 * @method string getMostRecentSubscriptionUserGuid()
 * @method string setMostRecentSubscriptionUserGuid(string $targetUserGuid)
 */
class RepositoryOptions implements ValidatableObjectInterface
{
    use MagicAttributes;

    private ?ValidationError $validationError = null;

    /**
     * @type int
     */
    private const HARD_LIMIT = 150;

    /**
     * @var int Maximum amount of recommendations to be returned
     */
    private int $limit = 12;

    /**
     * @var int The position to start our search from
     */
    private int $offset = 0;

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

    /**
     * Runs the validation of the options and returns false at the first failure encountered, true otherwise.
     * @return bool
     */
    public function validate(): bool
    {
        if ($this->limit < 1) {
            $this->validationError = new ValidationError("limit", "You must provide a values of at least 1");
            return false;
        } elseif ($this->limit > self::HARD_LIMIT) {
            $this->validationError = new ValidationError("limit", "You must provide a value that does not exceed " . self::HARD_LIMIT);
            return false;
        }

        if ($this->offset < 0) {
            $this->validationError = new ValidationError("offset", "You must provide a values of at least 1");
            return false;
        } elseif ($this->offset > self::HARD_LIMIT - 1) {
            $this->validationError = new ValidationError("offset", "You must provide a value that does not exceed " . (self::HARD_LIMIT - 1));
            return false;
        }

        if (!is_numeric($this->targetUserGuid)) {
            $this->validationError = new ValidationError("targetUserId", "You must provide the guid of the target user");
            return false;
        }

        if ($this->mostRecentSubscriptionUserGuid != null && !is_numeric($this->mostRecentSubscriptionUserGuid)) {
            $this->validationError = new ValidationError("mostRecentSubscriptionUserGuid", "You must provide a valid guid for the most recent subscription's user guid");
            return false;
        }

        return true;
    }

    /**
     * Returns the first error
     * @return ValidationError|null
     */
    public function error(): ?ValidationError
    {
        return $this->validationError;
    }
}
