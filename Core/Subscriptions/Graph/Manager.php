<?php
namespace Minds\Core\Subscriptions\Graph;

use Exception;
use Minds\Common\Repository\Response;

/**
 * Subscriptions Graph Manager
 * @package Minds\Core\Subscriptions\Graph
 */
class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var string */
    protected $userGuid;

    /**
     * Manager constructor.
     * @param $repository
     */
    public function __construct(
        $repository = null
    ) {
        $this->repository = $repository ?: new Repository();
    }

    /**
     * Sets the current User GUID
     * @param string $userGuid
     * @return Manager
     */
    public function setUserGuid(string $userGuid): Manager
    {
        $this->userGuid = $userGuid;
        return $this;
    }

    /**
     * Fetchs a list of connections based on its type
     * @param RepositoryGetOptions $options
     * @return Response
     * @throws Exception
     */
    public function getList(RepositoryGetOptions $options): Response
    {
        if (!$this->userGuid) {
            throw new \Exception('Invalid User GUID');
        }

        $options
            ->setUserGuid($this->userGuid);

        switch ($options->getType()) {
            // TODO
            // case 'subscribers':
            //     return $this->repository->getSubscribers($options);

            case 'subscriptions':
                return $this->repository->getSubscriptions($options);

            default:
                throw new Exception('Invalid subscription list type');
        }
    }
}
