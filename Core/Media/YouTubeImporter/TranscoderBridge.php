<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Di\Di;
use Minds\Core\Media\Video\Transcoder\Manager as TranscoderManager;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;
use Minds\Core\Media\Video\Transcoder\TranscodeStorage\TranscodeStorageInterface;
use Minds\Entities\Video;

class TranscoderBridge
{
    // See https://gist.github.com/sidneys/7095afe4da4ae58694d128b1034e01e2#youtube-video-stream-format-codes
    public const YT_ITAGS_TO_PROFILES = [
        -1 => TranscodeProfiles\Thumbnails::class,
        18 => TranscodeProfiles\X264_360p::class,
        22 => TranscodeProfiles\X264_720p::class,
        37 => TranscodeProfiles\X264_1080p::class,
        43 => TranscodeProfiles\Webm_360p::class,
        45 => TranscodeProfiles\Webm_720p::class,
    ];

    /** @var TranscoderManager */
    protected $transcoderManager;

    /** @var TranscodeStorageInterface */
    protected $transcodeStorage;

    public function __construct(
        $transcoderManager = null,
        $transcodeStorage = null
    ) {
        $this->transcoderManager = $transcoderManager ?? Di::_()->get('Media\Video\Transcoder\Manager');
        $this->transcodeStorage = $transcodeStorage ?? Di::_()->get('Media\Video\Transcode\TranscodeStorage');
    }

    /**
     * Adds a youtube source to Minds transcode
     * @param Video $video
     * @param YTVideoSource $source
     * @return bool
     */
    public function addFromYouTube(Video $video, YTVideoSource $source): bool
    {
        $transcodeProfile = static::YT_ITAGS_TO_PROFILES[$source->getItag()] ?? null;

        if (!$transcodeProfile) {
            return false;
        }

        $transcode = new Transcode();
        $transcode
            ->setVideo($video)
            ->setProfile(new $transcodeProfile)
            ->setStatus(TranscodeStates::TRANSCODING);

        if ($transcode->getProfile() instanceof TranscodeProfiles\Thumbnails) {
            $transcode->getProfile()->setStorageName("thumbnail-00001.png");
        }

        // We send 360p videos to be retranscoded
        $this->transcoderManager->add($transcode, false);

        $tmpFile = tmpfile();
        $tmpPath = stream_get_meta_data($tmpFile)['uri'];
        file_put_contents($tmpPath, fopen($source->getUrl(), 'r'));

        $reTranscode = $transcode->getProfile() instanceof TranscodeProfiles\X264_360p;
        if ($reTranscode) {
            // Create a source
            $source = new Transcode();
            $source
                ->setVideo($video)
                ->setProfile(new TranscodeProfiles\Source());

            // Upload this source to s3
            $this->transcodeStorage->add($source, $tmpPath);

            // Submit to transcoder queues (cloned to avoid updateing the transcoder status)
            $this->transcoderManager->add(clone $transcode, true);
            
            // Still proceed so that web can complete quickly
        }

        $transcode->setStatus(TranscodeStates::COMPLETED)
            ->setProgress(100);

        $success = $this->transcodeStorage->add($transcode, $tmpPath);
        $this->transcoderManager->update($transcode, [ 'status', 'progress' ]);

        fclose($tmpFile); // remove the tmpFile
        
        return $success;
    }
}
