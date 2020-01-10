<?php
namespace Minds\Core\Analytics\Dashboards;

interface DashboardInterface
{
    /**
     * Build the dashboard
     * NOTE: return type not specified due to php
     * having terrible typing support
     * @return self
     */
    public function build();

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array;
}
