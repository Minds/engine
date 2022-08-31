<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Validators;

use Minds\Core\Di\Di;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\SupermindRequestReplyType;
use Minds\Entities\Activity;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;

class SupermindReplyValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors;

    public function __construct(
        private ?SupermindManager $supermindManager = null
    ) {
        $this->supermindManager ??= Di::_()->get("Supermind\Manager");
    }

    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    public function validate(array $dataToValidate): bool
    {
        $this->resetErrors();

        if (isset($dataToValidate['mature']) && $dataToValidate['mature'] === true) {
            $this->errors->add(
                new ValidationError(
                    "mature",
                    "A Supermind request cannot be marked as NSFW"
                )
            );
        }

        if (isset($dataToValidate['paywall'])) {
            $this->errors->add(
                new ValidationError(
                    "paywall",
                    "A Supermind request cannot be monetized"
                )
            );
        }

        // TODO: Add validation for schedule option
        // TODO: Add validation for ellipsis menu options

        if (!isset($dataToValidate['supermind_reply_guid'])) {
            $this->errors->add(
                new ValidationError(
                    "supermind_reply_guid",
                    "The target Supermind request guid must be provided"
                )
            );
        }

        $supermindRequest = $this->supermindManager->getRequest($dataToValidate['supermind_reply_guid']);

        /**
         * @var Activity $activity
         */
        $activity = $dataToValidate['activity'];

        switch ($supermindRequest->getReplyType()) {
            case SupermindRequestReplyType::TEXT:
                if ($activity->getType() === "object") {
                    $this->errors->add(
                        new ValidationError(
                            "supermind_reply_guid",
                            "The reply type does not match the requested type"
                        )
                    );
                }
                break;
            case SupermindRequestReplyType::IMAGE:
                if ($activity->getSubtype() !== "image") {
                    $this->errors->add(
                        new ValidationError(
                            "supermind_reply_guid",
                            "The reply type does not match the requested type"
                        )
                    );
                }
                break;
            case SupermindRequestReplyType::VIDEO:
                if ($activity->getSubtype() !== "video") {
                    $this->errors->add(
                        new ValidationError(
                            "supermind_reply_guid",
                            "The reply type does not match the requested type"
                        )
                    );
                }
                break;
        }

        return $this->errors->count() === 0;
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
