<?php

namespace Minds\Core\Feeds\Supermind\Exceptions;

use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;

class SupermindFeedBadRequestException extends UserErrorException
{
    /**
     * @param $message
     * @param $code
     * @param ValidationErrorCollection|null $errors
     */
    public function __construct($message = "", $code = 0, ?ValidationErrorCollection $errors = null)
    {
        parent::__construct($message, $code, $errors);
    }
}
