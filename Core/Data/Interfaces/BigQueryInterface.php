<?php

namespace Minds\Core\Data\Interfaces;

/**
 * BigQuery query interface.
 */
interface BigQueryInterface
{
    /**
     * Get from BigQuery
     * @return Iterable - Iterable BigQuery rows.
     */
    public function get(): Iterable;
}
