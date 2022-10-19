<?php
/**
 * Stripe Payment Intent
 */
namespace Minds\Core\Payments\Stripe\Intents;

use Minds\Core\Log\Logger;
use Minds\Traits\MagicAttributes;

/**
 * @method int getAmount()
 * @method PaymentIntent getQuantity(): int
 * @method PaymentIntent getCurrency(): string
 * @method PaymentIntent getConfirm(): bool
 * @method PaymentIntent getOffSession(): bool
 * @method PaymentIntent getServiceFeePct(): int
 * @method PaymentIntent setCaptureMethod($method)
 * @method PaymentIntent getDescriptor(): string
 * @method bool isOffSession()
 * @method bool isConfirm()
 * @method string getCaptureMethod()
 * @method array getMetadata()
 * @method self setMetadata(array $metadata)
 */
class PaymentIntent extends Intent
{
    use MagicAttributes;

    /** @var int $amount */
    private $amount = 0;

    /** @var int $quantity */
    private $quantity = 1;

    /** @var string $currency */
    private $currency = 'usd';

    /** @var boolean $confirm */
    private $confirm = false;

    /** @var string */
    private $captureMethod = 'automatic';

    /** @var bool $offSession */
    private $offSession = false;

    /** @var string $descriptor */
    private $descriptor = 'MINDS, INC.';

    /** @var int $serviceFeePct */
    private $serviceFeePct = 0;

    private array $metadata = [];

    public function __construct(
        private ?Logger $logger = null
    ) {
        $this->logger ??= new Logger();
    }

    /**
     * Return the service
     * @return int
     */
    public function getServiceFee(): int
    {
        return round($this->amount * ($this->serviceFeePct / 100));
    }

    /**
     * Set descriptor for payment. Cannot be more than 22 characters
     * or will log error and use default value instead.
     * @param string $descriptor - descriptor to set (max of 22 character).
     * @return self
     */
    public function setDescriptor(string $descriptor, bool $usePrefix = true): self
    {
        if ($usePrefix) {
            $descriptor = "Minds: $descriptor";
        }

        // if descriptor is more than 22 characters, log an error and don't set so that default is used.
        if (strlen($descriptor) > 22) {
            $this->logger->error("PaymentIntent descriptor must be less than 22 characters: '$descriptor'");
            return $this;
        }

        $this->descriptor = $descriptor;
        return $this;
    }

    /**
     * Expose to the public apis
     * @param array $extend
     * @return array
     */
    public function export(array $extend = []) : array
    {
        return [
        ];
    }
}
