<?php
/**
 * Ownership
 * @author edgebal
 */

namespace Minds\Core\Groups;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Group;
use Minds\Entities\User;

class Ownership
{
    /** @var Membership */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var string */
    protected $userGuid;

    /**
     * Ownership constructor.
     * @param Membership $manager
     * @param EntitiesBuilder $entitiesBuilder
     */
    public function __construct(
        $manager = null,
        $entitiesBuilder = null
    ) {
        $this->manager = $manager ?: new Membership();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param int|string $userGuid
     * @return Ownership
     */
    public function setUserGuid($userGuid): Ownership
    {
        $this->userGuid = (string) $userGuid;
        return $this;
    }

    /**
     * @param array $opts
     * @return Response
     * @throws Exception
     */
    public function fetch(array $opts = [])
    {
        $opts = array_merge([
            'cap' => 500,
            'offset' => 0,
            'limit' => 12,
        ], $opts);

        $guids = $this->manager->getGroupGuidsByMember([
            'user_guid' => $this->userGuid,
            'limit' => $opts['cap']
        ]);

        $offset = $opts['offset'] ?: 0;
        $limit = $opts['limit'];

        $guids = array_slice($guids, $offset, $limit ?: null);

        if (!$guids) {
            return new Response();
        }

        $user = new User();
        $user->guid = $this->userGuid;

        $response = (new Response(
            $this->entitiesBuilder->get(['guids' => $guids])
        ))->filter(function ($group) use ($user) {
            /** @var Group $group */
            return $group && $group->isPublic() && $group->isOwner($user);
        })->sort(function ($a, $b) {
            /** @var Group $a */
            /** @var Group $b */

            return $b->getMembersCount() <=> $a->getMembersCount();
        });

        $response
            ->setPagingToken($offset + $limit);

        return $response;
    }
}
