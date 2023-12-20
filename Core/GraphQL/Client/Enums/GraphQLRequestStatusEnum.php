<?php

namespace Minds\Core\GraphQL\Client\Enums;

enum GraphQLRequestStatusEnum
{
    case SUCCESS;
    case BAD_REQUEST;
    case ERROR;
}
