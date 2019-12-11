<?php
/**
 * Source. This is the source file.
 * It will not be transcoded but will be stored on the filestore
 */
namespace Minds\Core\Media\Video\Transcoder\TranscodeProfiles;

class Source extends AbstractTranscodeProfile
{
    /** @var string */
    protected $format = 'video/*';

    /** @var int */
    protected $width = 0;

    /** @var int */
    protected $height = 0;

    /** @var string */
    protected $storageName = 'source';
}
