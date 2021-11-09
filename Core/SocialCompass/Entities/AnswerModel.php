<?php

namespace Minds\Core\SocialCompass\Entities;

use Cassandra\Bigint;
use Minds\Entities\ValidatableObjectInterface;
use Minds\Entities\ValidationErrorCollection;
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

    public function __construct(
        protected Bigint $userGuid,
        protected string $questionId,
        protected int $currentValue
    ) {
    }

    public function validate(): ValidationErrorCollection
    {
        return new ValidationErrorCollection();
    }
}
