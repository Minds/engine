<?php
/**
 * Minds FFMpeg.
 */

namespace Minds\Core\Media\Video\Transcoder\TranscodeExecutors;

use FFMpeg\FFMpeg as FFMpegClient;
use FFMpeg\FFProbe as FFProbeClient;
use FFMpeg\Media\Video as FFMpegVideo;
use FFMpeg\Filters\Video\ResizeFilter;
use Minds\Core;
use Minds\Core\Config;
use Minds\Entities\Video;
use Minds\Core\Di\Di;
use Minds\Core\Media\TranscodingStatus;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Core\Media\Video\Transcoder\TranscodeStorage\TranscodeStorageInterface;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;

class FFMpegExecutor implements TranscodeExecutorInterface
{
    /** @var Config */
    private $config;

    /** @var FFMpeg */
    private $ffmpeg;

    /** @var FFProbe */
    private $ffprobe;

    /** @var TranscodeStorageInterface */
    private $transcodeStorage;

    public function __construct(
        $config = null,
        $ffmpeg = null,
        $ffprobe = null,
        $transcodeStorage = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->ffmpeg = $ffmpeg ?: FFMpegClient::create([
            'ffmpeg.binaries' => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'ffmpeg.threads' => $this->config->get('transcoder')['threads'],
            'timeout' => 0,
        ]);
        $this->ffprobe = $ffprobe ?: FFProbeClient::create([
            'ffprobe.binaries' => '/usr/bin/ffprobe',
        ]);
        $this->transcodeStorage = $transcodeStorage ?? Di::_()->get('Media\Video\Transcode\TranscodeStorage');
    }

    /**
     * Transcode the video
     * @param Transcode $transcode (pass by reference)
     * @param callable $progressCallback
     * @return bool
     */
    public function transcode(Transcode &$transcode, callable $progressCallback): bool
    {
        // This is the profile that will be used for the transcode
        $transcodeProfiler = $transcode->getProfile();

        // Prepare the source of this transcode
        $source = new Transcode();
        $source->setGuid($transcode->getGuid())
            ->setProfile(new TranscodeProfiles\Source()); // Simply change the source

        // Download the source
        try {
            $sourcePath = $this->transcodeStorage->downloadToTmp($source);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            throw new FailedTranscodeException("Error downloading {$transcode->getGuid()} from storage");
        }

        // Open the resource
        /** @var \FFMpeg\Media\Video; */
        $video = $this->ffmpeg->open($sourcePath);

        if (!$video) {
            throw new FailedTranscodeException("Source error");
        }

        $tags = null;

        try {
            $videostream = $this->ffprobe
                ->streams($sourcePath)
                ->videos()
                ->first();

            if (!$videostream) {
                throw new FailedTranscodeException("Video stream not found");
            }

            // get video metadata
            $tags = $videostream->get('tags');
        } catch (\Exception $e) {
            error_log('Error getting videostream information');
        }

        // Thumbnails are treated differently to other transcodes
        if ($transcode->getProfile() instanceof TranscodeProfiles\Thumbnails) {
            return $this->transcodeThumbnails($transcode, $sourcePath, $video);
        }

        // Target height
        $width = $transcodeProfiler->getWidth();
        $height = $transcodeProfiler->getHeight();

        // Logic for rotated videos
        $rotated = isset($tags['rotate']) && in_array($tags['rotate'], [270, 90], false);
        if ($rotated && $videostream) {
            $ratio = $videostream->get('width') / $videostream->get('height');
            // Invert width and height
            $width = $height;
            $height = round($height * $ratio);
        }

        // Resize the video
        $video->filters()
            ->resize(
                new \FFMpeg\Coordinate\Dimension($width, $height),
                $rotated ? ResizeFilter::RESIZEMODE_FIT : ResizeFilter::RESIZEMODE_SCALE_WIDTH
            )
            ->synchronize();

        $pfx = $transcodeProfiler->getStorageName();
        $path = $sourcePath.'-'.$pfx;
        $format = $transcodeProfiler->getFormat();
    
        $formatMap = [
            'video/mp4' => (new \FFMpeg\Format\Video\X264())
                ->setAudioCodec('aac'),
            'video/webm' => new \FFMpeg\Format\Video\WebM(),
        ];

        try {
            // $this->logger->info("Transcoding: $path ({$transcode->getGuid()})");

            // Update our progress
            $formatMap[$format]->on('progress', function ($a, $b, $pct) use ($progressCallback) {
                // $this->logger->info("$pct% transcoded");
                $progressCallback($pct);
            });

            $formatMap[$format]
                ->setKiloBitRate($transcodeProfiler->getBitrate())
                ->setAudioKiloBitrate($transcodeProfiler->getAudioBitrate());
         
            // Run the transcode
            $video->save($formatMap[$format], $path);

            // Save to storage
            $this->transcodeStorage->add($transcode, $path);

            // Completed!
            // $this->logger->info("Completed: $path ({$transcode->getGuid()})");
            $transcode->setStatus(TranscodeStates::COMPLETED);
        } catch (\Exception $e) {
            error_log("FAILED: {$transcode->getGuid()} {$e->getMessage()}");
            // $this->logger->out("Failed {$e->getMessage()}");
            $transcode->setStatus(TranscodeStates::FAILED);
            // TODO: Should we also save the failure reason in the db?
            // Throw a new 'failed' exception
            throw new FailedTranscodeException($e->getMessage());
        } finally {
            // Cleanup our path
            @unlink($path);
        }

        // Cleanup our sourcefile
        @unlink($sourcePath);

        return true;
    }

    /**
     * Thumbnail transcodes are treated differently as they we extract frames
     * @param Transcode $transcode
     * @return bool
     */
    protected function transcodeThumbnails(Transcode &$transcode, string $sourcePath, FFMpegVideo $video): bool
    {
        try {
            // Create a temporary directory for out thumbnails
            $thumbnailsDir = $sourcePath . '-thumbnails';
            @mkdir($thumbnailsDir, 0600, true);

            // Create thumbnails
            $length = round((int) $this->ffprobe->format($sourcePath)->get('duration'));
            $secs = [0, 1, round($length / 2), $length - 1];
            foreach ($secs as $sec) {
                $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($sec));
                $pad = str_pad($sec, 5, '0', STR_PAD_LEFT);
                $path = $thumbnailsDir.'/'."thumbnail-$pad.png";
                $frame->save($path);

                // Hack the profile storage name, as there are multiple thumbnails
                $transcode->getProfile()->setStorageName("thumbnail-$pad.png");

                // Upload to filestore
                $this->transcodeStorage->add($transcode, $path);
    
                // Cleanup tmp
                @unlink($path);
            }
            $transcode->setProgress(100);
            $transcode->setStatus(TranscodeStates::COMPLETED);
        } catch (\Exception $e) {
            error_log("FAILED: {$transcode->getGuid()} {$e->getMessage()}");
            $transcode->setStatus(TranscodeStates::FAILED);
            // TODO: Should we also save the failure reason in the db?
            // Throw a new 'failed' exception
            throw new FailedTranscodeException($e->getMessage());
        } finally {
            // Cleanup the temporary directory we made
            @unlink($thumbnailsDir);
        }
        return true;
    }
}
