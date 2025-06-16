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
        Lists\LastPostedList::class,
        Lists\BoostedV3List::class,
        Lists\TokenBalances::class,
        Lists\WireUSDUsersList::class,
        Lists\MonetizedUsersList::class,
        Lists\TwitterSyncList::class,
        Lists\YoutubeSyncList::class,
        Lists\EthUsersList::class,
        Lists\MembershipTierOwnerList::class,
        Lists\Active30DayList::class,
        Lists\TenantsList::class,
        Lists\MatrixList::class,
        // Takes too long
        // Lists\SubscribersList::class,
    ];

    public function __construct(
        protected ?Repository $repository = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?EmailPreferenceLists $emailPreferenceLists = null,
        protected ?Logger $logger = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->repository ??= Di::_()->get(Repository::class);
        $this->emailPreferenceLists ??= Di::_()->get(EmailPreferenceLists::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @param int $fromTs (optional)
     * @return iterable<array>
     */
    public function getList(int $fromTs = null, int $offset = 0): iterable
    {
        foreach ($this->repository->getList(fromTs: $fromTs, offset: $offset) as $row) {
            $user = $this->entitiesBuilder->single($row['user_guid']);

            if (!$user instanceof User) {
                continue;
            }

            $row['guid'] = $user->getGuid();
            $row['email'] = $user->getEmail();
            //$row['lastActive'] = date('c', $user->last_login);
            $row['lastActive'] = $row['last_active_30_day_ts'] ?? date('c', $user->last_login); // More accurate than last login
            $row['time_created'] = date('c', $user->getTimeCreated());
            $row['is_admin'] = ($user->admin == 'yes');
            $row['verified_email'] = $user->isTrusted();
            $row['is_enabled'] = $user->isEnabled();
            $row['kite_state'] = $user->kite_state;

            // Construct email preference lists
            foreach ($this->emailPreferenceLists->getList($user->getGuid()) as $emailSubscription) {
                $key = substr(implode('_', [
                    'pref',
                    $emailSubscription->getCampaign(),
                    $emailSubscription->getTopic(),
                ]), 0, 25);
                $row[$key] = $emailSubscription->getValue() === "0" ? false : true; // Empty we imply they are opted in
            }

            yield $row;
        }
        return;
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

                $this->logger->debug("$i: (" . get_class($list) . ") {$export['first_name']}");
            }
        }
    }

    public function add($user_guid, $attributeName, $attributeValue): bool
    {
        return $this->repository->add($user_guid, $attributeName, $attributeValue);
    }
}
