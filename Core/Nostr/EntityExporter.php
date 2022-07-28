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

        // Query all nostr events based off filters
        foreach ($this->manager->getNostrEvents($filters) as $nostrEvent) {
            yield $nostrEvent;
        }

        // Are authors being requested? If so, is it a Kind:1? If so, get the users directly from `nostr_users` first
        // and then query all `source=nostr` from `nostr_events` and all others from elastic.
        if ($filters['authors']) {
            try {
                foreach ($this->manager->getElasticNostrEventsForAuthors($filters['authors']) as $nostrEvent) {
                    yield $nostrEvent;
                }
            } catch (\Minds\Exceptions\NotFoundException) {
                // Do nothing
            }
        }
    }
}
