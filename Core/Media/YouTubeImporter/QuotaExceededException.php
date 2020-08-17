<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Exceptions\UserErrorException;

class QuotaExceededException extends UserErrorException
{
    /** @var string */
    protected $message = "Due to high demand we have exceeded our quota. Please try again tomorrow!";
}
