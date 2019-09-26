<?php
namespace Minds\Core\Analytics\Dashboards;

class Manager
{
    const DASHBOARDS = [
        'traffic' => TrafficDashboard::class,
    ];

    /**
     * @param string $id
     * @return DashboardInterface
     */
    public function getDashboardById(string $id): DashboardInterface
    {
        return self::DASHBOARDS[$id];
    }
}
