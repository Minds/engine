<?php
/**
 * Source. This is the source file.
 * It will not be transcoded but will be stored on the filestore
 */
namespace Minds\Core\Media\Video\Transcoder\TranscodeProfiles;

class Factory
{
    /**
     * Build a TranscodeProfileInterface from ID
     * @param string $profileId
     * @return TranscodeProfile
     * @throws TranscodeProfileNotFoundException
     */
    public static function build(string $profileId): TranscodeProfileInterface
    {
        $class = "Minds\\Core\\Media\\Video\\Transcoder\\TranscodeProfiles\\$profileId";
        
        if (class_exists($class)) {
            return new $class;
        }

        throw new TranscodeProfileNotFoundException("$profileId does not have a valid profile instance");
    }
}
