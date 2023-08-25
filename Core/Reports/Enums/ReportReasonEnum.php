<?php

namespace Minds\Core\Reports\Enums;

enum ReportReasonEnum: int
{
    case ILLEGAL = 1;
    case NSFW = 2;
    case INCITEMENT_TO_VIOLENCE = 3;
    case HARASSMENT = 4;
    case PERSONAL_CONFIDENTIAL_INFORMATION = 5;
    case IMPERSONATION = 7;
    case SPAM = 8;
    case INTELLECTUAL_PROPERTY_VIOLATION = 10;
    case MALWARE = 13;
    case INAUTHENTIC_ENGAGEMENT = 16;
    case SECURITY = 17;
    case ANOTHER_REASON = 11;
    case VIOLATES_PREMIUM_CONTENT_POLICY = 18;
    case ACTIVITY_PUB_REPORT = 19;
}
