<?php
/**
 * All entities should use this interface
 */
namespace Minds\Entities;

interface EntityInterface
{
    /**
     * @return string
     * */
    public function getGuid(): ?string;

    /**
     * @return string
     */
    public function getOwnerGuid(): ?string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getSubtype(): ?string;

    /**
     * @return string
     */
    public function getUrn(): string;

    /**
     * @return string
     */
    public function getAccessId(): string;
}
