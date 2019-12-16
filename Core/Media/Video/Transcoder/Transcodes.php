<?php
/**
 * Transcode model
 */
namespace Minds\Core\Media\Video\Transcoder;

use Minds\Traits\MagicAttributes;

class Transcodes
{
    use MagicAttributes;

    /** @var string */
    private $guid;

    /** @var Transcode[] */
    private $transcodes;

    public function export($extras = []): array
    {
        return [
            'guid' => $this->guid,
            'transcodes' => $this->transcodes ? array_map(function ($transcode) {
                return $transcode->export();
            }, $this->transcodes) : null
        ];
    }
}
