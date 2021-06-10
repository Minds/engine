<?php
namespace Minds\Core\Notifications\EmailDigests;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * A marker to reserve a place in the email digest queue
 * @method string getToGuid()
 * @method self setToGuid(string $toGuid)
 * @method string getFrequency()
 * @method self setFrequency()
 * @method int getTimestamp()
 * @method self setTimestamp()
 */
class EmailDigestMarker implements ExportableInterface
{
    use MagicAttributes;

    /** @var string */
    const FREQUENCY_DAILY = 'daily';

    /** @var string */
    const FREQUENCY_WEEKLY = 'weekly';

    /** @var string */
    const FREQUENCY_PERIODICALLY = 'periodically';

    /** @var string */
    protected $toGuid;

    /** @var string */
    protected $frequency = self::FREQUENCY_DAILY;

    /** @var int */
    protected $timestamp;

    public function export(array $extras = []): array
    {
        return [
            'frequency' => $this->frequency,
            'timestamp' => $this->timestamp,
        ];
    }
}
