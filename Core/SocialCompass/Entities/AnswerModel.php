<?php

namespace Minds\Core\SocialCompass\Entities;

use Cassandra\Bigint;
use Minds\Traits\MagicAttributes;

/**
 * @method Bigint getUserGuid()
 * @method self setUserGuid(Bigint $userGuid)
 *
 * @method string getQuestionId()
 * @method self setQuestionId(string $questionId)
 *
 * @method int getCurrentValue()
 * @method self setCurrentValue(int $currentValue)
 */
class AnswerModel
{
    use MagicAttributes;

    public function __construct(
        protected Bigint $userGuid,
        protected string $questionId,
        protected int $currentValue
    )
    {
    }

    public function export(): array
    {

    }
}
