<?php
namespace Minds\Core\Media\Video\CloudflareStreams;

use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Traits\MagicAttributes;

/**
 * @method string getPct()
 * @method TranscodeStatus setPct(int $pct)
 * @method string getState()
 * @method TranscodeStatus setState(TranscodeStates $state)
 */
class TranscodeStatus
{
    use MagicAttributes;

    /**
     * Progress percentage of the uploaded video
     * @var int
     * */
    protected $pct;

    /** @var TranscodeStates */
    protected $state;
}
