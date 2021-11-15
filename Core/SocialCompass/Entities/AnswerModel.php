<?php

namespace Minds\Core\SocialCompass\Entities;

use Cassandra\Bigint;
use Minds\Core\SocialCompass\Questions\BaseQuestion;
use Minds\Entities\ValidatableObjectInterface;
use Minds\Entities\ValidationError;
use Minds\Traits\MagicAttributes;

/**
 * The model representing an answer to the Social Compass questions
 *
 * @method Bigint getUserGuid()
 * @method self setUserGuid(Bigint $userGuid)
 *
 * @method string getQuestionId()
 * @method self setQuestionId(string $questionId)
 *
 * @method int getCurrentValue()
 * @method self setCurrentValue(int $currentValue)
 */
class AnswerModel implements ValidatableObjectInterface
{
    use MagicAttributes;

    private ?ValidationError $error;

    public function __construct(
        protected Bigint $userGuid,
        protected string $questionId,
        protected int $currentValue
    ) {
    }

    public function validate(): bool
    {
        $question = $this->getRelatedQuestion();

        $error = new ValidationError(field: $this->questionId);

        if (!$this->isCurrentValueInRange($question)) {
            $this->error = new ValidationError(
                $this->questionId,
                "The answer to the question needs to be between {$question->getMinimumRangeValue()} and {$question->getMaximumRangeValue()}"
            );
            return false;
        }

        if (!$this->isCurrentValueAValidIncrement($question)) {
            $this->error = new ValidationError(
                $this->questionId,
                "The answer to the question is not within the defined increments."
            );
            return false;
        }

        return true;
    }

    public function error(): ?ValidationError
    {
        return $this->error;
    }

    private function getRelatedQuestion(): BaseQuestion
    {
        $questionClassNamespace = "Minds\\Core\\SocialCompass\\Questions\\{$this->questionId}";
        return new $questionClassNamespace();
    }

    private function isCurrentValueInRange(BaseQuestion $question): bool
    {
        return $this->currentValue >= $question->getMinimumRangeValue() && $this->currentValue <= $question->getMaximumRangeValue();
    }

    private function isCurrentValueAValidIncrement(BaseQuestion $question): bool
    {
        return ($this->currentValue % $question->getStepSize()) == 0;
    }
}
