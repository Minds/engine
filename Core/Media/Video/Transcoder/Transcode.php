<?php
/**
 * Transcode model
 */
namespace Minds\Core\Media\Video\Transcoder;

use Minds\Entities\Video;
use Minds\Traits\MagicAttributes;

/**
 * @method Transcode setGuid(string $guid)
 * @method string getGuid()
 * @method Transcode setVideo(Video $video)
 * @method Video getVideo()
 * @method Transcode setProgress(int $progress)
 * @method int getProgress()
 * @method Transcode setStatus(string $status)
 * @method string getStatus()
 * @method TranscodeProfiles\TranscodeProfileInterface getProfile()
 * @method int getLastEventTimestampMs()
 * @method Transcode setLastEventTimestampMs(int $lastEventTimestampMs)
 * @method Transcode setLengthSecs(int $secs)
 * @method int getLengthSecs()
 * @method Transcode setBytes(int $bytes)
 * @method int getBytes()
 * @method Transcode setFailureReason(string $reason)
 * @method string getFailureReason()
 */
class Transcode
{
    use MagicAttributes;

    /** @var string */
    const TRANSCODE_STATES = [
        TranscodeStates::CREATED,
        TranscodeStates::TRANSCODING,
        TranscodeStates::FAILED,
        TranscodeStates::COMPLETED,
    ];

    /** @var string */
    private $guid;

    /** @var Video */
    private $video;

    /** @var TranscodeProfiles\TranscodeProfileInterface */
    private $profile;

    /** @var int */
    private $progress = 0;

    /** @var string */
    protected $status;

    /** @var int */
    private $lastEventTimestampMs;

    /** @var int */
    private $lengthSecs;

    /** @var int */
    private $bytes;

    /** @var string */
    private $failureReason;

    /** @var bool */
    private $completed;

    /**
     * @param Video $video
     * @return self
     */
    public function setVideo(Video $video): self
    {
        $this->video = $video;
        $this->guid = $video->getGuid();
        return $this;
    }

    /**
     * Set the profile
     * @param TranscodeProfiles\TranscodeProfileInterface $profile
     * @throws TranscodeProfiles\UnavailableTranscodeProfileException
     * @return self
     */
    public function setProfile(TranscodeProfiles\TranscodeProfileInterface $profile): self
    {
        if ($profile->isProOnly() && $this->video && !$this->video->getOwnerEntity(false)->isPro()) {
            throw new TranscodeProfiles\UnavailableTranscodeProfileException();
        }
        $this->profile = $profile;
        return $this;
    }

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'guid' => $this->guid,
            'profile' => $this->profile->export(),
            'progress' => (int) $this->progress,
            'completed' => (bool) $this->completed,
            'last_event_timestamp_ms' => (int) $this->lastEventTimestampMs,
            'length_secs' => (int) $this->lengthSecs,
            'bytes' => (int) $this->bytes,
        ];
    }
}
