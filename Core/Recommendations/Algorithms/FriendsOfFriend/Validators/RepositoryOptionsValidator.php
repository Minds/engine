<?php

namespace Minds\Core\Recommendations\Algorithms\FriendsOfFriend\Validators;

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

        if (!isset($dataToValidate['targetUserGuid']) || !$dataToValidate['targetUserGuid']) {
            $this->errors->add(new ValidationError(
                "targetUserGuid",
                "You must provide a guid for the target user"
            ));
        } elseif (!is_numeric($dataToValidate['targetUserGuid'])) {
            $this->errors->add(new ValidationError(
                "targetUserId",
                "You must provide a valid guid for the target user"
            ));
        }

        if (!isset($dataToValidate['mostRecentSubscriptionUserGuid']) || !$dataToValidate['mostRecentSubscriptionUserGuid']) {
            $this->errors->add(new ValidationError(
                "mostRecentSubscriptionUserGuid",
                "You must provide a guid for the most recent subscription's user guid"
            ));
        } elseif (!is_numeric($dataToValidate['mostRecentSubscriptionUserGuid'])) {
            $this->errors->add(new ValidationError(
                "mostRecentSubscriptionUserGuid",
                "You must provide a valid guid for the most recent subscription's user guid"
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
