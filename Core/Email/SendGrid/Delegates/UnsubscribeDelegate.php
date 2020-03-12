<?php
namespace Minds\Core\Email\SendGrid\Delegates;

class UnsubscribeDelegate
{
    /**
     * Called from webhook. Assumed authenticated/validated
     * @param array $event
     * @return boid
     */
    public function onWebhook(array $event): void
    {
        // Todo: SendGrid is handling these lists for now
        // but we should maintain in the future
        error_log(print_r($event, true));
    }
}
