<?php

namespace Minds\Core\Recommendations\Algorithms\FriendsOfFriends\Validators;

use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

/**
 * Validates the repository options for the FriendsOfFriends recommendation algorithm
 */
class RepositoryOptionsValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors;

    private const HARD_LIMIT = 150;

    private function clearErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    /**
     * @inheritDoc
     */
    public function validate(array $dataToValidate): bool
    {
        $this->clearErrors();

        if ($dataToValidate['limit'] < 1) {
            $this->errors->add(new ValidationError(
                "limit",
                "You must provide a values of at least 1"
            ));
        } elseif ($dataToValidate['limit'] > self::HARD_LIMIT) {
            $this->errors->add(new ValidationError(
                "limit",
                "You must provide a value that does not exceed " . self::HARD_LIMIT
            ));
        }

        if (!is_numeric($dataToValidate['targetUserGuid'])) {
            $this->errors->add(new ValidationError(
                "targetUserId",
                "You must provide the guid of the target user"
            ));
        }

        if (!$dataToValidate['currentChannelUserGuid'] || !is_numeric($dataToValidate['currentChannelUserGuid'])) {
            $this->errors->add(new ValidationError(
                "currentChannelUserGuid",
                "You must provide a valid guid for the current channel's user guid"
            ));
        }

        if ($dataToValidate['mostRecentSubscriptions'] && !is_array($dataToValidate['mostRecentSubscriptions'])) {
            $this->errors->add(new ValidationError(
                "mostRecentSubscriptionUserGuid",
                "You must provide a valid list of guid representing the most recent subscriptions for the target user"
            ));
        }

        return $this->errors->count() == 0;
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
