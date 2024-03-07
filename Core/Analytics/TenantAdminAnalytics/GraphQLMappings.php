<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\TenantAdminAnalytics;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

/**
 * GraphQL mappings for tenant admin analytics.
 */
class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Analytics\TenantAdminAnalytics\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\\Core\\Analytics\\TenantAdminAnalytics\\Enums');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\AnalyticsChartType::class,
            Types\AnalyticsKpiType::class,
            Types\Chart\AnalyticsChartBucketType::class,
            Types\Chart\AnalyticsChartSegmentType::class,
            Types\AnalyticsTableConnection::class,
            
            Types\Table\AnalyticsTableRowNodeInterface::class,
            Types\Table\AnalyticsTableRowEdge::class,
            Types\Table\AnalyticsTableRowActivityNode::class,
            Types\Table\AnalyticsTableRowGroupNode::class,
            Types\Table\AnalyticsTableRowUserNode::class,
        ]));
    }
}
