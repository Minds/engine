<?php

namespace Minds\Core\Feeds\RSS\Enums;

enum RssFeedLastFetchStatusEnum: int
{
    case SUCCESS = 1;
    case FAILED_TO_CONNECT = 2;
    case FAILED_TO_PARSE = 3;
}
