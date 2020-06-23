<?php
namespace Minds\Core\Wire\Paywall;

interface PaywallEntityInterface
{
    /**
     * Set the entity as being paywalled
     * @param bool $paywalled
     * @return self
     */
    public function setPayWall(bool $paywalled);

    /**
     * Return if the entity is paywalled
     * @return bool
     */
    public function isPayWall(): bool;

    /**
     * Set the paywall threshold data (or support tier for v2)
     * @param array $wireThreshold
     * @return self
     */
    public function setWireThreshold(array $wireThreshold);

    /**
     * Returns the paywall threshold data
     * @return array
     */
    public function getWireThreshold(): ?array;
}
