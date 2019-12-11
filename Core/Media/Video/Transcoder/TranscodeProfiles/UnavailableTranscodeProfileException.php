<?php
/**
 * Unavailable Transcode Profile Exceptioon
 */
namespace Minds\Core\Media\Video\Transcoder\TranscodeProfiles;

class UnavailableTranscodeProfileException extends \Exception
{
    /** @var string */
    protected $message = 'This transcode profile is not available';
}
