<?php
namespace Minds\Core\Comments\EmbeddedComments\Exceptions;

use Minds\Exceptions\UserErrorException;

class OwnerDisabledAutoImportsException extends UserErrorException
{
    protected $message = "The ownerGuid has disabled auto imports for their comments plugin";
}
