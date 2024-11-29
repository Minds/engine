<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\ActivityPatchers;

use Laminas\Feed\Reader\Entry\EntryInterface;
use Minds\Entities\Activity;
use Minds\Entities\User;

/**
 * Class that patches existing activities with data from RSS entries
 * of specific types, e.g. Audio - where there are bespoke fields to set.
 */
interface RssActivityPatcherInterface
{
    /**
     * Patches an activity for an RSS entry.
     * @param Activity $activity - The activity to patch.
     * @param EntryInterface $entry - The RSS entry.
     * @param User $owner - The owner of the activity.
     * @param array $richEmbedData - Rich embed data.
     * @return Activity - The patched activity.
     */
    public function patch(
        Activity $activity,
        EntryInterface $entry,
        User $owner,
        ?array $richEmbedData = null,
    ): Activity;
}
