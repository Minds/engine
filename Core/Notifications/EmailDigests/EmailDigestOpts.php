<?php
namespace Minds\Core\Notifications\EmailDigests;

use Minds\Common\Repository\AbstractRepositoryOpts;

/**
 * @method string getFrequency()
 * @method self setFrequency()
 * @method int getTimestamp()
 * @method self setTimestamp()
 */
class EmailDigestOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $frequency = EmailDigestMarker::FREQUENCY_PERIODICALLY;

    /** @var int */
    protected $timestamp;
}
