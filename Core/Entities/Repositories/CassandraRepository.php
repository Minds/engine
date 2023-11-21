<?php
namespace Minds\Core\Entities\Repositories;

use Minds\Core\Blogs\Blog;
use Minds\Core\Data\Call;
use Minds\Core\Data\Cassandra\Thrift\Indexes;
use Minds\Core\Data\lookup;
use Minds\Entities\Video;
use Minds\Entities\Activity;
use Minds\Entities\Factory;
use Minds\Entities\EntityInterface;
use Minds\Entities\Group;
use Minds\Entities\Image;
use Minds\Entities\Object\Carousel;
use Minds\Entities\User;

class CassandraRepository implements EntitiesRepositoryInterface
{
    public function __construct(
        private Call $entitiesTable,
        private lookup $lookupTable,
        private Indexes $indexesTable,
    ) {
        
    }

    /**
     * @inheritDoc
     */
    public function loadFromGuid(int|array $guid): mixed
    {
        $row = $this->entitiesTable->getRow($guid, [ 'limit' => 5000 ]);

        if (!$row) {
            return null;
        }

        $row['guid'] = $guid;

        $entity = Factory::build($row);

        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function loadFromIndex(string $index, string $value): ?EntityInterface
    {
        // Cassandra only maintains one index
        $values = $this->lookupTable->get(strtolower($value));

        if (!$values) {
            return null;
        }

        $guid = key($values);
        $user = $this->loadFromGuid($guid);

        if ($user && $user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function create(EntityInterface $entity): bool
    {
        switch (get_class($entity)) {
            case User::class:
            case Activity::class:
            case Image::class:
            case Video::class:
            case Group::class:
            case Carousel::class:
                /**  @var User|Activity|Image|Video|Group|Carousel */
                $entity = $entity;
                $data = $entity->toArray();
                break;
            default:
                throw new \Exception('Can not save this entity type');
        }

        switch (get_class($entity)) {
            case User::class:
                /**
                 * Create the user indexes
                 */
                $lookupData = [$entity->getGuid() => time()];

                if (!$this->lookupTable->get(strtolower($entity->getUsername()))) {
                    $this->lookupTable->set(strtolower($entity->getUsername()), $lookupData);
                    $this->lookupTable->set(strtolower($entity->getEmail()), $lookupData);
                    if ($entity->phone_number_hash) {
                        $this->lookupTable->set(strtolower($entity->phone_number_hash), $lookupData);
                    }
                }
                break;
        }

        if (!$this->entitiesTable->insert($entity->getGuid(), $data)) {
            return false;
        }

        foreach ($this->getIndexKeys($entity) as $index) {
            $this->indexesTable->insert($index, [ $entity->getGuid() => time() ]);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function update(EntityInterface $entity, array $columns = []): bool
    {
        switch (get_class($entity)) {
            case User::class:
            case Activity::class:
            case Image::class:
            case Video::class:
            case Group::class:
            case Blog::class:
                /**  @var User|Activity|Image|Video|Group|Blog */
                $entity = $entity;
                $data = $entity->toArray();
                break;
            default:
                throw new \Exception('Can not save this entity type');
        }

        if ($columns) {
            $data = array_filter($data, function ($k) use ($columns) {
                return in_array($k, $columns, false);
            }, ARRAY_FILTER_USE_KEY);
        }

        // Always save the timestamp for when last updated
        $data['time_updated'] = time();
    
        if (!$this->entitiesTable->insert($entity->getGuid(), $data)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(EntityInterface $entity): bool
    {
        // Remove the actual entity
        if (!$this->entitiesTable->removeRow($entity->getGuid())) {
            return false;
        }
    
        // Cleanup cassandra indexes
        foreach ($this->getIndexKeys($entity, true) as $rowkey) {
            $this->indexesTable->remove($rowkey, [$entity->getGuid()]);
        }

        switch (get_class($entity)) {
            case User::class:
                /**
                 * Remove the user indexes
                 */
                /** @var User */
                $entity = $entity;
                if ($this->lookupTable->get(strtolower($entity->getUsername()))) {
                    $this->lookupTable->remove(strtolower($entity->getUsername()));
                    $this->lookupTable->remove(strtolower($entity->getEmail()));
                    if ($entity->phone_number_hash) {
                        $this->lookupTable->remove(strtolower($entity->phone_number_hash));
                    }
                }
                break;
        }

        return true;
    }

    /**
     * Returns an array of indexes into which this entity is stored
     *
     * @param bool $ia - ignore access
     * @return array
     */
    protected function getIndexKeys(EntityInterface $entity, $ia = false)
    {
        if ($entity->access_id == ACCESS_PUBLIC || $ia) {
            $indexes = [
                $entity->type,
                "$entity->type:$entity->subtype"
            ];

            if ($entity->super_subtype) {
                array_push($indexes, "$entity->type:$entity->super_subtype");
            }
        } else {
            $indexes = [];
        }

        if (!$entity->hidden) {
            array_push($indexes, "$entity->type:$entity->super_subtype:user:$entity->owner_guid");
            array_push($indexes, "$entity->type:$entity->subtype:user:$entity->owner_guid");
        } else {
            array_push($indexes, "$entity->type:$entity->super_subtype:user:$entity->owner_guid:hidden");
            array_push($indexes, "$entity->type:$entity->subtype:user:$entity->owner_guid:hidden");
        }

        array_push($indexes, "$entity->type:container:$entity->container_guid");
        array_push($indexes, "$entity->type:$entity->subtype:container:$entity->container_guid");

        return $indexes;
    }
}
