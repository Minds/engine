<?php
namespace Minds\Core\Feeds\HideEntities;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\HideEntities\Exceptions\InvalidEntityException;
use Minds\Core\Feeds\HideEntities\Exceptions\TooManyHiddenException;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

class Manager
{
    /** @var int */
    const DEFAULT_MAX_HIDE_24H = 5;

    protected User $user;

    public function __construct(
        private ?Repository $repository = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Config $config = null,
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * @param User $user
     * @return Manager
     */
    public function withUser(User $user): Manager
    {
        $instance = clone $this;
        $instance->user = $user;
        return $instance;
    }

    /**
     * @param string $guid
     * @return bool
     */
    public function hideEntityByGuid(string $guid): bool
    {
        $entity = $this->entitiesBuilder->single($guid);

        if (!$entity instanceof Activity) {
            throw new InvalidEntityException();
        }

        $hideEntity = new HideEntity($this->user->getGuid(), $entity->getGuid());

        /**
         * If not plus, there is a threhsold of how many posts they can hide per day
         */
        if (!$this->user->isPlus()
            && $this->repository->count($this->user->getGuid(), gt: strtotime('24 hours ago')) >= $this->getMaxHides24h()
        ) {
            throw new TooManyHiddenException();
        }

        return $this->repository->add($hideEntity);
    }

    /**
     * @return int
     */
    protected function getMaxHides24h(): int
    {
        return $this->config->get('max_hidden_entities_24h') ?: static::DEFAULT_MAX_HIDE_24H;
    }
}
