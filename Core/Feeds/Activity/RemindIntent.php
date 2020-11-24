<?php

namespace Minds\Core\Feeds\Activity;

use Minds\Traits\MagicAttributes;

class RemindIntent
{
    use MagicAttributes;

    /** @var string */
    protected $guid;

    /** @var string */
    protected $ownerGuid;

    /** @var bool */
    protected $quotedPost;

    /**
     * Export the intent
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'guid' => (string) $this->guid,
            'owner_guid' => (string) $this->ownerGuid,
            'quoted_post' => (bool) $this->quotedPost,
        ];
    }
}
