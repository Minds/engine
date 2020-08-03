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
     * Set the paywall to be unlocked
     * @param bool $unlocked
     * @return self
     */
    public function setPayWallUnlocked(bool $unlocked);

    /**
     * Return if the entity is unlocked
     * @return bool
     */
    public function isPayWallUnlocked(): bool;

    /**
     * Set the paywall threshold data (or support tier for v2)
     * @param array $wireThreshold
     * @return self
     */
    public function setWireThreshold($wireThreshold = []);

    /**
     * Returns the paywall threshold data
     * @return array
     */
    public function getWireThreshold(): ?array;
}
