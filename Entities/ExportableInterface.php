<?php
/**
 * All exportable entities/items should use this
 */
namespace Minds\Entities;

interface ExportableInterface
{
    /**
     * @param array $extras
     * @return array
     * */
    public function export(array $extras = []): array;
}
