<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Exceptions;

use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class InvalidChatRoomTypeException extends GraphQLException
{
    protected $message = 'Invalid room type';
    protected $code = 400;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? $this->message;
        parent::__construct($this->message, $this->code);
    }
}
