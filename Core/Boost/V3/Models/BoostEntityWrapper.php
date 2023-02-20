<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Models;

use Minds\Entities\ExportableInterface;

/**
 * Class representing a V3 boost entity wrapper. Should be used when you
 * want to return a boosted entity, that has "boosted" attributes as
 * can be seen in the export. These attributes allow clients to make a
 * distinction between, for example, an activity, and a boosted activity.
 */
class BoostEntityWrapper implements ExportableInterface
{
    public function __construct(
        private Boost $boost
    ) {
        $this->boost = $boost;
    }

    /**
     * Export a boosts entity, patching in boost specific attributes
     * for client convenience.
     * @param array $extras - extra parameters to add to the export.
     * @return array exported boost entity.
     */
    public function export(array $extras = []): array
    {
        $export = $this->boost->getEntity()?->export();
        $export['boosted'] = true;
        $export['boosted_guid'] = $this->boost->getGuid();
        $export['urn'] = $this->boost->getUrn();

        return count($extras) ? [
            ...$export,
            ...$extras
        ] : $export;
    }
}
