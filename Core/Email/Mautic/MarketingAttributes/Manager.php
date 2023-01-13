<?php
namespace Minds\Core\Email\Mautic\MarketingAttributes;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Email\SendGrid\Lists;
use Minds\Entities\User;

/**
 * This manager will sync all our attributes to a single table for better performance of syncing with mautic
 */
class Manager
{
    /** @var SendGridListInterface[] */
    const DEFAULT_LISTS = [
        Lists\WireUSDUsersList::class,
        Lists\MonetizedUsersList::class,
        Lists\TwitterSyncList::class,
        Lists\YoutubeSyncList::class,
        //Lists\EthUsersList::class,
        Lists\MembershipTierOwnerList::class,
        Lists\Active30DayList::class,
        Lists\SubscribersList::class,
    ];

    public function __construct(
        protected ?Repository $repository = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Logger $logger = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->repository ??= Di::_()->get(Repository::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @param int $fromTs (optional)
     * @return iterable
     */
    public function getList(int $fromTs = null): iterable
    {
        foreach ($this->repository->getList(fromTs: $fromTs) as $row) {
            $user = $this->entitiesBuilder->single($row['user_guid']);

            if (!$user instanceof User) {
                continue;
            }

            $row['guid'] = $user->getGuid();
            $row['email'] = $user->getEmail();
            $row['lastActive'] = date('c', $user->last_login);

            yield $row;
        }
    }

    /**
     * Syncs our lists to the database
     * @param SendGridListInterface[] $lists
     * @return void
     */
    public function sync(array $lists = []): void
    {
        if (empty($lists)) {
            $lists = array_map(function ($list) {
                return new $list;
            }, self::DEFAULT_LISTS);
        }
        $i = 0;
        foreach ($lists as $list) {
            foreach ($list->getContacts() as $contact) {
                ++$i;

                $export = $contact->export();

                $mauticContact = [];

                /**
                 * Loops through custom fields and convert to minds
                 * Potentially manipulate
                 */
                foreach ($export['custom_fields'] as $key => $value) {
                    $mauticContact[$key] = $value;
                }

                foreach ($mauticContact as $k => $v) {
                    $this->repository->add($contact->getUserGuid(), $k, $v);
                }

                $this->logger->info("$i: (" . get_class($list) . ") {$export['first_name']}");
            }
        }
    }

    public function add($user_guid, $attributeName, $attributeValue): bool
    {
        return $this->repository->add($user_guid, $attributeName, $attributeValue);
    }
}
