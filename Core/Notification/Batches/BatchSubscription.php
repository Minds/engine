<?php
/**
 * Notification BatchId
 */
namespace Minds\Core\Notification\Batches;

use Minds\Traits\MagicAttributes;

/**
 * @method string getUserGuid()
 * @method string getBatchId()
 */
class BatchSubscription
{
    use MagicAttributes;

    /** @param string $userGuid */
    private $userGuid;

    /** @param string $batchId */
    private $batchId;

    /**
     * Export
     * @return array
     */
    public function export()
    {
        return [
            'userGuid' => $this->getUserGuid(),
            'batchId' => $this->getBatchId(),
        ];
    }
}
