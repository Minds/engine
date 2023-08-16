<?php
/**
 * Entities that will be federated should implement the following functions
 */
namespace Minds\Entities;

use Minds\Entities\Enums\FederatedEntitySourcesEnum;

interface FederatedEntityInterface
{
    /**
     * @return FederatedEntitySourcesEnum
     * */
    public function getSource(): ?FederatedEntitySourcesEnum;

    /**
     * @return self
     */
    public function setSource(FederatedEntitySourcesEnum $source): self;

    /**
     * @return string|null
     */
    public function getCanonicalUrl(): ?string;

    /**
    * @return self
    */
    public function setCanonicalUrl(string $canonicalUrl): self;

}
