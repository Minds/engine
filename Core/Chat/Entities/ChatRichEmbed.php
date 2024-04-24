<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Entities;

use DateTimeInterface;

/**
 * Chat rich embed model.
 */
class ChatRichEmbed
{
    public function __construct(
        public readonly string $url,
        public readonly string $canonicalUrl,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $author,
        public readonly ?string $thumbnailSrc,
        public readonly ?DateTimeInterface $createdTimestamp,
        public readonly ?DateTimeInterface $updatedTimestamp
    ) {
    }

    /**
     * Export object as array.
     * @return array Exported array.
     */
    public function export(): array
    {
        return [
            'url' => $this->url,
            'canonicalUrl' => $this->canonicalUrl,
            'title' => $this->title,
            'description' => $this->description,
            'author' => $this->author,
            'thumbnailSrc' => $this->thumbnailSrc,
            'createdTimestamp' => $this->createdTimestamp,
            'updatedTimestamp' => $this->updatedTimestamp
        ];
    }
}
