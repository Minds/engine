<?php
/**
 * 360p Webm
 */
namespace Minds\Core\Media\Video\Transcoder\TranscodeProfiles;

class Webm_360p extends AbstractTranscodeProfile
{
    /** @var string */
    protected $format = 'video/webm';

    /** @var int */
    protected $width = 640;

    /** @var int */
    protected $height = 360;

    /** @var int */
    protected $bitrate = 500;

    /** @var int */
    protected $audioBitrate = 80;

    /** @var bool */
    protected $proOnly = false;

    /** @var string */
    protected $storageName = '360.webm';
}
