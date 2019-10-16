<?php
namespace Minds\Core\Analytics\Dashboards;

class Manager
{
    const DASHBOARDS = [
        'traffic' => TrafficDashboard::class,
        'trending' => TrendingDashboard::class,
        'earnings' => EarningsDashboard::class,
    ];

    /**
     * @param string $id
     * @return DashboardInterface
     */
    public function getDashboardById(string $id): DashboardInterface
    {
        $class = self::DASHBOARDS[$id];
        return new $class;
    }
}
