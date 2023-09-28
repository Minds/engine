<?php
namespace Minds\Core\Entities\Services;

use Minds\Core\Entities\Repositories\CassandraRepository;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;
use Minds\Entities\EntityInterface;

class EntitiesRepositoryService
{
    public function __construct(
        private EntitiesRepositoryInterface $repository,
    )
    {
        
    }

    public function loadFromGuid(int $guid): ?EntityInterface
    {
        return $this->repository->loadFromGuid($guid);
    }

    public function loadFromIndex(int $guid)

    
}