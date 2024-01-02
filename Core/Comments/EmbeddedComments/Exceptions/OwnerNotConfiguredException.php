<?php
namespace Minds\Core\Comments\EmbeddedComments\Exceptions;

use Minds\Exceptions\UserErrorException;

class OwnerNotConfiguredException extends UserErrorException
{
    protected $message = "The ownerGuid provided has not configured their comments plugin";
}
