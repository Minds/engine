<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Validators;

use Minds\Core\Di\Di;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
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

    /**
     * @param array $dataToValidate
     * @return bool
     * @throws SupermindNotFoundException
     */
    public function validate(array $dataToValidate): bool
    {
        $this->resetErrors();

        if (isset($dataToValidate['mature']) && $dataToValidate['mature'] === true) {
            $this->errors->add(
                new ValidationError(
                    "mature",
                    "A Supermind reply cannot be marked as NSFW"
                )
            );
        }

        if (!empty($dataToValidate['time_created'])) {
            $this->errors->add(
                new ValidationError(
                    "time_created",
                    "A Supermind reply cannot be a scheduled post"
                )
            );
        }

        if (isset($dataToValidate['post_to_permaweb']) && $dataToValidate['post_to_permaweb']) {
            $this->errors->add(
                new ValidationError(
                    "post_to_permaweb",
                    "A Supermind request cannot be a Permaweb post"
                )
            );
        }

        if (isset($dataToValidate['access_id']) && $dataToValidate['access_id'] != ACCESS_PUBLIC) {
            $this->errors->add(
                new ValidationError(
                    'access_id',
                    "A Supermind request must be a public post"
                )
            );
        }

        if (isset($dataToValidate['license']) && $dataToValidate['license'] !== "all-rights-reserved") {
            $this->errors->add(
                new ValidationError(
                    "license",
                    "A Supermind request must have an 'All Rights Reserved' license applied"
                )
            );
        }

        if (isset($dataToValidate['wire_threshold']) || isset($dataToValidate['paywall'])) {
            $this->errors->add(
                new ValidationError(
                    isset($dataToValidate['wire_threshold']) ? 'wire_threshold' : 'paywall',
                    'A Supermind request cannot be monetized'
                )
            );
        }

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
            case SupermindRequestReplyType::IMAGE:
                if ($activity->getCustomType() !== "batch") {
                    $this->errors->add(
                        new ValidationError(
                            "supermind_reply_guid",
                            "The reply type does not match the requested type"
                        )
                    );
                }
                break;
            case SupermindRequestReplyType::VIDEO:
                if ($activity->getCustomType() !== "video") {
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
