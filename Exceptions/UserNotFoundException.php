<?php

namespace Minds\Exceptions;

class UserNotFoundException extends NotFoundException
{
    protected $message = "User not found";
}
