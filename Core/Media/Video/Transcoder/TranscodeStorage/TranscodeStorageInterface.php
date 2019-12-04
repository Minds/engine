<?php
namespace Minds\Core\Media\Video\Transcoder\TranscodeStorage;

use Minds\Core\Media\Video\Transcoder\Transcode;

interface TranscodeStorageInterface
{
    /**
     * @param Transcode $transcode
     * @param string $path
     * @return bool
     */
    public function add(Transcode $transcode, string $path): bool;

    /**
     * @param Transcode $transcode
     * @return string
     */
    public function downloadToTmp(Transcode $transcode): string;
}
