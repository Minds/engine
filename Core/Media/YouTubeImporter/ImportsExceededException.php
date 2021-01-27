<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Exceptions\UserErrorException;

class ImportsExceededException extends UserErrorException
{
    /** @var string */
    protected $message = "You have already exceeded your maximum imports for today. Please come back tomorrow.";
}
