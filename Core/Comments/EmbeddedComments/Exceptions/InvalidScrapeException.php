<?php
namespace Minds\Core\Comments\EmbeddedComments\Exceptions;

use Minds\Exceptions\UserErrorException;

class InvalidScrapeException extends UserErrorException
{
    protected $message = "We were unable to scrape the url";
}
