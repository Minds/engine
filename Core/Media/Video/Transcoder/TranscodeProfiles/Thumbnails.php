<?php
/**
 * Thumbnails
 */
namespace Minds\Core\Media\Video\Transcoder\TranscodeProfiles;

class Thumbnails extends AbstractTranscodeProfile
{
    /** @var string */
    protected $format = 'image/png';

    /** @var int */
    protected $width = 1920;

    /** @var int */
    protected $height = 1080;
}
