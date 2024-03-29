<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;

/**
 * Abstract Notice to be extended by Feed Notices.
 */
abstract class AbstractNotice
{
    // instance user.
    protected ?User $user = null;

    public function __construct(
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Get location of notice in feed.
     * @return string location of notice in feed.
     */
    abstract public function getLocation(): string;

    /**
     * Get notice key (identifier for notice).
     * @return string notice key.
     */
    abstract public function getKey(): string;

    /**
     * Whether notice should show in feed.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    abstract public function shouldShow(User $user): bool;
    
    /**
     * Whether notice is dismissible.
     * @return boolean - true if notice is dismissible.
     */
    abstract public function isDismissible(): bool;

    /**
     * Set user for instance.
     * @param User $user - user to set.
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Whether is from the context of a tenant.
     * @return bool - true if this is from a tenant context.
     */
    public function isTenantContext(): bool
    {
        return (bool) $this->config->get('tenant_id');
    }

    /**
     * Export notice as array with key, location and should_show.
     * @return array - exported notice.
     */
    public function export(): array
    {
        return [
            'key' => $this->getKey(),
            'location' => $this->getLocation(),
            'should_show' => $this->shouldShow(
                $this->user
            ),
            'is_dismissible' => $this->isDismissible()
        ];
    }
}
