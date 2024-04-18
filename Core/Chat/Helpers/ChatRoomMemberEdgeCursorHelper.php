<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Helpers;

class ChatRoomMemberEdgeCursorHelper
{
    /**
     * @param int $memberGuid
     * @param int|null $joinedTimestamp
     * @return string
     */
    public static function generateCursor(
        int $memberGuid,
        ?int $joinedTimestamp
    ): string {
        return $joinedTimestamp ?
            base64_encode((string)$joinedTimestamp) :
            base64_encode("0:$memberGuid");
    }

    /**
     * @return array{
     *     joinedTimestamp: ?int,
     *     memberGuid: ?int
     * }
     */
    public static function readCursor(?string $cursor): array
    {
        $cursorDetails = [
            'joinedTimestamp' => null,
            'memberGuid' => null,
        ];

        if (!$cursor) {
            return $cursorDetails;
        }

        $offsetParts = explode(':', base64_decode($cursor, true));

        if (count($offsetParts) === 1) {
            $cursorDetails['joinedTimestamp'] = (int)$offsetParts[0];
        } else {
            $cursorDetails = [
                'memberGuid' => (int)$offsetParts[1],
            ];
        }

        return $cursorDetails;
    }
}
