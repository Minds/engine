<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\Push\ManualSend\Interfaces;

use Minds\Core\Notifications\Push\ManualSend\Models\ManualSendRequest;

interface ManualSendServiceInterface
{
    /**
     * Send a manual push notification
     * @param ManualSendRequest $request - request containing data used to send.
     * @return bool true on success.
     */
    public function send(ManualSendRequest $request): bool;
}
