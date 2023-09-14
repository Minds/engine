<?php
namespace Minds\Core\Payments\GiftCards\Enums;

/**
 * Status filter enum for gift cards.
 */
enum GiftCardStatusFilterEnum: int
{
    /** When a gift card is active (not expired) */
    case ACTIVE = 0;

    /** When a gift card is expired */
    case EXPIRED = 1;
}
