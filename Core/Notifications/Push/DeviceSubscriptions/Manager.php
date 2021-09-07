<?php
namespace Minds\Core\Notifications\Push\DeviceSubscriptions;

class Manager
{
    /** @var Repository */
    protected $repository;

    public function __construct(Repository $repository = null)
    {
        $this->repository = $repository ?? new Repository();
    }

    /**
     * @param DeviceSubscriptionListOpts $opts
     * @return iterable<DeviceSubscription>
     */
    public function getList(DeviceSubscriptionListOpts $opts): iterable
    {
        return $this->repository->getList($opts);
    }

    /**
     * @param DeviceSubscription $deviceSubscription
     * @return bool
     */
    public function add(DeviceSubscription $deviceSubscription): bool
    {
        return $this->repository->add($deviceSubscription);
    }

    /**
     * @param DeviceSubscription $deviceSubscription
     * @return bool
     */
    public function delete(DeviceSubscription $deviceSubscription): bool
    {
        return $this->repository->delete($deviceSubscription);
    }
}
