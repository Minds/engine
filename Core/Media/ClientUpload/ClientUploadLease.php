<?php

namespace Minds\Core\Media\ClientUpload;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * Class ClientUploadLease
 * @package Minds\Core\Media\ClientUpload
 * @method string getGuid()
 * @method ClientUploadLease setGuid(string $value)
 * @method string getMediaType()
 * @method ClientUploadLease setMediaType(string $value)
 * @method string getPresignedUrl()
 * @method ClientUploadLease setPresignedUrl(string $value)
 * @method User getUser()
 * @method ClientUploadLease setUser(User $value)
 */
class ClientUploadLease
{
    use MagicAttributes;

    /** @var string $guid */
    private $guid;

    /** @var string $presignedUrl */
    private $presignedUrl;

    /** @var string $mediaType */
    private $mediaType;

    /** @var User $user */
    private $user;

    /**
     * Export to API
     * @param array $extra
     * @return array
     */
    public function export($extra = [])
    {
        return [
            'guid' => (string) $this->getGuid(),
            'presigned_url' => $this->getPresignedUrl(),
            'media_type' => $this->getMediaType(),
        ];
    }
}
