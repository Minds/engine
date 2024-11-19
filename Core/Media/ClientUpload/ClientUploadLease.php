<?php

namespace Minds\Core\Media\ClientUpload;

/**
 * Class ClientUploadLease
 */
class ClientUploadLease
{
    public function __construct(
        public readonly int $guid,
        public readonly MediaTypeEnum $mediaType,
        public readonly ?string $presignedUrl = null,
    ) {
        
    }

    /**
     * Export to API
     * @param array $extra
     * @return array
     */
    public function export($extra = [])
    {
        return [
            'guid' => (string) $this->guid,
            'presigned_url' => $this->presignedUrl,
            'media_type' => $this->mediaType,
        ];
    }

    public function getMediaType()
    {
        // TODO: write logic here
    }
}
