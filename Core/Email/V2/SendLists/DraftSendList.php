<?php
/**
 * Send list for testing emails
 */

namespace Minds\Core\Email\V2\SendLists;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Di\Di;

class DraftSendList extends AbstractSendList implements SendListInterface
{
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var string[] */
    private $userGuids = [];

    public function __construct($entitiesBuilder = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Sets arguments that the cli has provided
     * @param array $cliOpts
     * @return self
     */
    public function setCliOpts(array $cliOpts = []): self
    {
        foreach ($cliOpts as $k => $v) {
            switch ($k) {
                case "user-guids":
                    $this->userGuids = explode(',', $v);
                    break;
            }
        }

        return $this;
    }

    /**
     * Fetch all the users who are subscribed to a certain email campaign/topic
     */
    public function getList(): iterable
    {
        foreach ($this->userGuids as $userGuid) {
            $user = $this->entitiesBuilder->single($userGuid);
            yield $user;
        }
    }
}
