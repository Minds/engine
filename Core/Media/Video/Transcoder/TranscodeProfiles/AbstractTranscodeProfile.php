<?php
/**
 * Abstract class for transcode profiles
 */
namespace Minds\Core\Media\Video\Transcoder\TranscodeProfiles;

use Minds\Traits\MagicAttributes;

/**
 * @method string getFormat()
 * @method int getWidth()
 * @method int getHeight()
 * @method int getBitrate()
 * @method int getAudioBitrate()
 * @method bool isProOnly()
 * @method string getStorageName()
 * @method TranscodeProfileInterface setStorageName(string $storageName)
 */
abstract class AbstractTranscodeProfile implements TranscodeProfileInterface
{
    use MagicAttributes;

    /** @var string */
    protected $format;

    /** @var int */
    protected $width;

    /** @var int */
    protected $height;

    /** @var int */
    protected $bitrate;

    /** @var int */
    protected $audioBitrate;

    /** @var bool */
    protected $proOnly = false;

    /** @var string */
    protected $storageName;

    /**
     * Returns the ID of the transcode (this will usually be the classname)
     * @return string
     */
    public function getId(): string
    {
        $path = explode('\\', get_called_class());
        return array_pop($path);
    }

    /**
     * Export the profile
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'id' => $this->getId(),
            'format' => $this->format,
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'bitrate' => (int) $this->bitrate,
            'audio_bitrate' => (int) $this->audioBitrate,
        ];
    }
}
