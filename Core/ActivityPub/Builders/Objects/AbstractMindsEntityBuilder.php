<?php
declare(strict_types=1);

namespace Minds\Core\ActivityPub\Builders\Objects;

use Minds\Core\ActivityPub\Types\Object\DocumentType;

abstract class AbstractMindsEntityBuilder
{
    /**
     * @param array $attachments
     * @return array
     */
    protected function processAttachments(array $attachments): array
    {
        return array_map(function ($attachment): DocumentType {
            $document = new DocumentType();
            $document->url = $attachment['src'];
            $document->mediaType = $attachment['mediaType'];

            if ($row['width'] ?? null) {
                $document->width = $attachment['width'];
            }
            if ($row['height'] ?? null) {
                $document->height = $attachment['height'];
            }

            return $document;
        }, $attachments);
    }
}
