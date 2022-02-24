<?php


namespace Minds\Core\Email\V2\SendLists;

use Minds\Core;
use Minds\Entities\User;

class GenericSendList extends AbstractSendList implements SendListInterface
{
    /** @var Repository */
    private $repository;

    /** @var Core\EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct($repository = null, $entitiesBuilder = null)
    {
        $this->repository = $repository ?: Core\Di\Di::_()->get('Email\Repository');
        $this->entitiesBuilder = $entitiesBuilder ?: Core\Di\Di::_()->get('EntitiesBuilder');
    }

    /**
     * Sets arguments that the cli has provided
     * @param array $cliOpts
     * @return self
     */
    public function setCliOpts(array $cliOpts = []): self
    {
        return $this;
    }

    /**
     * Fetch all the users who are subscribed to a certain email campaign/topic
     */
    public function getList(): iterable
    {
        while (true) {
            $options = [
                'campaign' => $this->campaign->getCampaign(),
                'topic' => $this->campaign->getTopic(),
                'value' => true,
                'limit' => 150,
                'offset' => base64_decode($this->offset, true)
            ];

            $result = $this->repository->getList($options);

            if (!$result || !$result['data'] || count($result['data']) === 0) {
                return;
            }

            $this->offset = $result['next'] !== '' ? $result['next'] : null;

            $guids = array_map(function ($item) {
                return $item->getUserGuid();
            }, $result['data']);

            foreach ($this->entitiesBuilder->get([
                'guids' => $guids
            ]) as $user) {
                yield $user;
            }
        }
    }
}
