<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Exceptions;

use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class ChatMessageNotFoundException extends GraphQLException
{
    protected $message = 'Chat message not found';
    protected $code = 404;

    public function __construct()
    {
        parent::__construct($this->message, $this->code);
    }
}
