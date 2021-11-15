<?php

namespace Minds\Core\SocialCompass\Validators;

use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Entities\ValidationErrorCollection;

/**
 * Defines the logic to validate the answers to the Social Compass questions
 */
class AnswersCollectionValidator implements CollectionValidatorInterface
{
    private ?ValidationErrorCollection $errors;

    /**
     * @param AnswerModel[] $answers
     */
    public function __construct(
        private array $answers
    ) {
    }

    public function validate(): bool
    {
        $errors = new ValidationErrorCollection();

        foreach ($this->answers as $answer) {
            if (!$answer->validate()) {
                $errors->add($answer->error());
            }
        }

        $this->errors = $errors->count() ? $errors : null;

        return !$this->errors?->count();
    }

    public function errors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
