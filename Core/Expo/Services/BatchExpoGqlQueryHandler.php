<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Services;

/**
 * Handler of batch GQL queries.
 */
class BatchExpoGqlQueryHandler
{
    /**
     * Utility functions to match batch query results shorter to parse.
     * @param array $batchResponse - the response from the batch query.
     * @return array - the response with the data in a more convenient format.
     */
    protected function formatBatchResponse(array $batchResponse): array
    {
        $response = [];

        foreach ($batchResponse as $responseItem) {
            $arrayKey = array_key_first($responseItem['data']);
            $innerArrayKey = array_key_first($responseItem['data'][$arrayKey]);
            $response[$innerArrayKey] = $responseItem['data'][$arrayKey][$innerArrayKey];
        }

        return $response;
    }
}
