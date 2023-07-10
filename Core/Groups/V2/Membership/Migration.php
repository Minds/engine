<?php
namespace Minds\Core\Groups\V2\Membership;

use DateTime;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Log\Logger;
use Minds\Core\Security\ACL;
use Minds\Entities\Group;

class Migration
{
    protected Scroll $scroll;
    protected Repository $repository;
    protected EntitiesBuilder $entitiesBuilder;
    protected Logger $logger;

    public function __construct()
    {
        ACL::_()->setIgnore(true);

        $this->scroll = new Scroll();
        $this->repository = Di::_()->get(Repository::class);
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $this->logger = Di::_()->get('Logger');
    }

    public function run(string &$pagingToken)
    {
        $prepared = new Custom();

        $prepared->query("SELECT * FROM relationships");

        // Loop for cassandra rows
        foreach ($this->scroll->request($prepared, $pagingToken) as $row) {
            $key = $row['key'];
            $keyParts = explode(':', $key);

            if (count($keyParts) < 3) {
                continue;
            }

            $groupGuid = $keyParts[0];
            $userGuid = $row['column1'];

            $joinedTimestamp = $row['value'];

            $group = $this->entitiesBuilder->single($groupGuid);

            if (!$group instanceof Group) {
                $this->logger->error("Group $groupGuid not found " . base64_encode($pagingToken));
                continue;
            }

            $membership = new Membership(
                groupGuid: (int) $groupGuid,
                userGuid: (int) $userGuid,
                createdTimestamp: new DateTime("@$joinedTimestamp"),
                membershipLevel: GroupMembershipLevelEnum::MEMBER,
            );

            if ($group->isModerator($userGuid)) {
                $membership->membershipLevel = GroupMembershipLevelEnum::MODERATOR;
            }

            if ($group->isOwner($userGuid)) {
                $membership->membershipLevel = GroupMembershipLevelEnum::OWNER;
            }

            try {
                $this->repository->add($membership);
                $this->logger->info("Migrated $groupGuid:$userGuid " . base64_encode($pagingToken));
            } catch (\Exception $e) {
                if ($e->getCode() === "23000") {
                    // Duplicate, lets just update the membership level
                    try {
                        $this->repository->updateMembershipLevel($membership);
                    } catch (\Exception $e) {
                        $this->logger->error("Failed $groupGuid:$userGuid with " . $e->getMessage());
                    }
                    continue;
                }
                $this->logger->error("Failed $groupGuid:$userGuid with " . $e->getMessage());
            }
        }
    }
}
