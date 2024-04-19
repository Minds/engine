<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Helpers;

class ChatRoomEdgeCursorHelper
{
    /**
     * @param int $roomCreatedAtTimestamp
     * @param int|null $lastMessageCreatedAtTimestamp
     * @return string
     */
    public static function generateCursor(
        int $roomCreatedAtTimestamp,
        ?int $lastMessageCreatedAtTimestamp
    ): string {
        return $lastMessageCreatedAtTimestamp ?
            base64_encode((string)$lastMessageCreatedAtTimestamp) :
            base64_encode("0:$roomCreatedAtTimestamp");
    }

    /**
     * @return array{
     *     lastMessageCreatedAtTimestamp: ?int,
     *     roomCreatedAtTimestamp: ?int
     * }
     */
    public static function readCursor(?string $cursor): array
    {
        $cursorDetails = [
            'lastMessageCreatedAtTimestamp' => null,
            'roomCreatedAtTimestamp' => null,
        ];

        if (!$cursor) {
            return $cursorDetails;
        }

        $offsetParts = explode(':', base64_decode($cursor, true));

        if (count($offsetParts) === 1) {
            $cursorDetails['lastMessageCreatedAtTimestamp'] = (int)$offsetParts[0];
        } else {
            $cursorDetails = [
                'roomCreatedAtTimestamp' => (int)$offsetParts[1],
            ];
        }

        return $cursorDetails;
    }
}
