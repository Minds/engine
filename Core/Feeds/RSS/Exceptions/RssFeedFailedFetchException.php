<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\Exceptions;

use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class RssFeedFailedFetchException extends GraphQLException
{
    public function __construct()
    {
        parent::__construct(
            message: "Failed to fetch RSS feed",
            code: 500
        );
    }
}
