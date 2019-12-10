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
     * This will return a url that can be used by an HTTP client
     * to upload the source file
     * @param Transcode $transcode
     * @return string
     */
    public function getClientSideUploadUrl(Transcode $transcode): string;

    /**
     * @param Transcode $transcode
     * @return string
     */
    public function downloadToTmp(Transcode $transcode): string;

    /**
     * Return a list of files from storage
     * @param string $guid
     * @return array
     */
    public function ls(string $guid): array;
}
