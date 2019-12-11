<?php
/**
 * 1080p MP4 (pro only)
 */
namespace Minds\Core\Media\Video\Transcoder\TranscodeProfiles;

class X264_1080p extends AbstractTranscodeProfile
{
    /** @var string */
    protected $format = 'video/mp4';

    /** @var int */
    protected $width = 1920;

    /** @var int */
    protected $height = 1080;

    /** @var int */
    protected $bitrate = 2000;

    /** @var int */
    protected $audioBitrate = 128;

    /** @var bool */
    protected $proOnly = true;

    /** @var string */
    protected $storageName = '1080.mp4';
}
