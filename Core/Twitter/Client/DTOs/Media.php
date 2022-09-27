<?php

declare(strict_types=1);

namespace Minds\Core\Twitter\Client\DTOs;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setMediaIds(array $mediaIds)
 * @method array getMediaIds()
 * @method self setTaggedUserIds(array $taggedUserIds)
 * @method array getTaggedUserIds()
 */
class Media implements ExportableInterface
{
    use MagicAttributes;

    private array $mediaIds;
    private array $taggedUserIds;

    public function export(array $extras = []): array
    {
        return [
            'media_ids' => $this->getMediaIds(),
            'tagged_user_ids' => $this->getTaggedUserIds()
        ];
    }
}
