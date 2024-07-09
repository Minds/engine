<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Webhooks\Services;

use Minds\Exceptions\ServerErrorException;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class WebhookEventBuilderService
{
    public function buildWebhookEvent(
        string $payload,
        string $signature,
        string $secret
    ): Event {
        try {
            return Webhook::constructEvent(
                payload: $payload,
                sigHeader: $signature,
                secret: $secret
            );
        } catch (UnexpectedValueException $e) {
            throw new ServerErrorException('Failed to construct event', previous: $e);
        } catch (SignatureVerificationException $e) {
            throw new ServerErrorException('Failed to verify signature', previous: $e);
        }
    }
}
