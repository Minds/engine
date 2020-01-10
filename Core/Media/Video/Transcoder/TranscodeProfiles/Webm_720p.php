<?php
/**
 * 720p Webm
 */
namespace Minds\Core\Media\Video\Transcoder\TranscodeProfiles;

class Webm_720p extends AbstractTranscodeProfile
{
    /** @var string */
    protected $format = 'video/webm';

    /** @var int */
    protected $width = 1280;

    /** @var int */
    protected $height = 720;

    /** @var int */
    protected $bitrate = 1000;

    /** @var int */
    protected $audioBitrate = 128;

    /** @var bool */
    protected $proOnly = false;

    /** @var string */
    protected $storageName = '720.webm';
}
