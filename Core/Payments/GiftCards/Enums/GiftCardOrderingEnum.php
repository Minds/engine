<?php
namespace Minds\Core\Payments\GiftCards\Enums;

enum GiftCardOrderingEnum
{
    /** Gift cards that were created in chronological order */
    case CREATED_ASC;

    /** Gift cards that were created in reverse chronological order */
    case CREATED_DESC;

    /** Gift cards that will expire soonest */
    case EXPIRING_ASC;

    /** Gift cards expiring furthest in the future */
    case EXPIRING_DESC;
}
