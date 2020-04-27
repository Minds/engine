<?php
namespace Minds\Core\Media\Video\Transcoder\TranscodeExecutors;

use Minds\Core\Media\Video\Transcoder\Transcode;

interface TranscodeExecutorInterface
{
    /**
     * @param Transcode $transcode
     * @return bool
     */
    public function transcode(Transcode &$transcode, callable $progressCallback): bool;
}
