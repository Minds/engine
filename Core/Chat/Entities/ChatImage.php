<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Entities;

use DateTimeInterface;
use Minds\Core\Di\Di;

/**
 * Chat image model.
 */
class ChatImage
{
    public function __construct(
        public readonly int $guid,
        public readonly int $roomGuid,
        public readonly int $messageGuid,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly ?string $blurhash,
        public readonly DateTimeInterface $createdTimestamp,
        public readonly DateTimeInterface $updatedTimestamp
    ) {
    }

    /**
     * Gets the url of the image.
     * @return string The url of the image.
     */
    public function getUrl(): string
    {
        $siteUrl = Di::_()->get('Config')->get('site_url') ?? 'https://www.minds.com/';
        return $siteUrl . "fs/v3/chat/image/{$this->roomGuid}/{$this->messageGuid}";
    }

    /**
     * Export object as array.
     * @return array Exported array.
     */
    public function export(): array
    {
        return [
            'guid' => $this->guid,
            'roomGuid' => $this->roomGuid,
            'messageGuid' => $this->messageGuid,
            'url' => $this->getUrl(),
            'createdTimestamp' => $this->createdTimestamp,
            'updatedTimestamp' => $this->updatedTimestamp
        ];
    }
}
