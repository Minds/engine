<?php
namespace Minds\Core\Entities\Repositories;

use Minds\Entities\EntityInterface;

interface EntitiesRepositoryInterface
{
    /**
     * Returns an entity (if exists) from the data store
     */
    public function loadFromGuid(int $guid): ?EntityInterface;

    /**
     * Returns an entity (if exists) by an index from the data store
     */
    public function loadFromIndex(string $index, string $value): ?EntityInterface;

    /**
     * Creates an entity and saves to the data store
     */
    public function create(EntityInterface $entity): bool;

    /**
     * Updates an entity and saves to the data store
     * Acts as an upsert.
     * Specify columns to only update those. Empty columns will update all columns
     * 
     * @param EntityInterface $entity
     * @param string[] $columns
     */
    public function update(EntityInterface $entity, array $columns = []): bool;

    /**
     * Removes an entity from the data store
     */
    public function delete(EntityInterface $entity): bool;
}