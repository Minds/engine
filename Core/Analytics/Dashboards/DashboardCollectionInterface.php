<?php
namespace Minds\Core\Analytics\Dashboards;

interface DashboardCollectionInterface
{
    /**
     * Export everything in the collection
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array;
}
