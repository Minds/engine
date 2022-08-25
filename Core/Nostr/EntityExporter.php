<?php

namespace Minds\Core\Nostr;

use Minds\Common\Access;
use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Security\ACL;
use Minds\Core\Feeds;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

/**
 * This class export NostrEvents and has logic for delivering from Minds Entities
 * or directly from nostr events themselves
 */
class EntityExporter
{
    public function __construct(
        protected ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
    }

    /**
     * Will return NostrEvents by filters
     * TODO: support more filters. Currently on ids and authors
     * @param array $filters
     * @return iterable<NostrEvent>
     */
    public function getNostrReq(array $filters = []): iterable
    {
        $filters = array_merge([
            'ids' => [],
            'authors' => [],
            'kinds' => [ 0, 1 ],
            'since' => null,
            'until' => null,
            'limit' => 12,
        ], $filters);

        // Cap limit
        $filters['limit'] = $filters['limit'] > 150 ? 150 : $filters['limit'];

        // # of sent events
        $count = 0;

        // Query all nostr events based off filters
        foreach ($this->manager->getNostrEvents($filters) as $nostrEvent) {
            $count++;
            yield $nostrEvent;
        }

        // Are authors being requested? If so, is it a Kind:1? If so, get the users directly from `nostr_users` first
        // and then query all `source=nostr` from `nostr_events` and all others from elastic.
        $esLimit = $filters['limit'] - $count;
        if (
            $esLimit > 0 && // If we have not yet reached the limit,
                (in_array(0, $filters['kinds'], true) || in_array(1, $filters['kinds'], true)) && // and we want kind 0 or 1, pull from Minds posts
                !(array_key_exists('#e', $filters) || array_key_exists('#p', $filters)) && // and we do not filter by "#e" or "#p"
                !(count($filters['ids']) > 0) // and we do not filter by "ids"
        ) {
            try {
                foreach ($this->manager->getElasticNostrEvents($filters, $esLimit) as $nostrEvent) {
                    yield $nostrEvent;
                }
            } catch (\Minds\Exceptions\NotFoundException) {
                // Do nothing
            }
        }
    }
}
