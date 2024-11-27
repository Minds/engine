<?php
declare(strict_types=1);

namespace Minds\Core\Media\MediaDownloader;

/**
 * A class responsible for downloading different types of media from a given URL.
 */
interface MediaDownloaderInterface
{
    /**
     * Downloads media from a URL
     * @param string $url - The URL to download from.
     * @return string|null - file contents.
     */
    public function download(string $url): ?string;
}
