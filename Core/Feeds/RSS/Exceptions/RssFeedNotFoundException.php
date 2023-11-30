<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\Exceptions;

use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class RssFeedNotFoundException extends GraphQLException
{
    public function __construct()
    {
        parent::__construct(
            message: "RSS feed not found",
            code: 404
        );
    }
}
