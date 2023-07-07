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
 * @method PaymentIntent getStatementDescriptor(): string
 * @method PaymentIntent setDescription(string $description)
 * @method PaymentIntent getDescription(): string
 * @method bool isOffSession()
 * @method bool isConfirm()
 * @method string getCaptureMethod()
 * @method array getMetadata()
 * @method self setMetadata(array $metadata)
 */
class PaymentIntent extends Intent
{
    use MagicAttributes;

    private string $userGuid;

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

    /** @var string $statementDescriptor - statement descriptor to appear on statements */
    private $statementDescriptor = 'MINDS, INC.';

    /** @var string $description - longer form description for more detailed tracking */
    private $description = '';

    /** @var int $serviceFeePct */
    private $serviceFeePct = 0;

    private array $metadata = [];

    public function __construct(
        private ?Logger $logger = null
    ) {
        $this->logger ??= new Logger();
    }

    /**
     * @return string
     */
    public function getUserGuid(): string
    {
        return $this->userGuid;
    }

    /**
     * @param string|int $userGuid
     * @return PaymentIntent
     */
    public function setUserGuid(string|int $userGuid): self
    {
        $this->userGuid = (string) $userGuid;
        return $this;
    }

    /**
     * Return the service
     * @return int
     */
    public function getServiceFee(): int
    {
        return (int) round($this->amount * ($this->serviceFeePct / 100));
    }

    /**
     * Set statement descriptor for payment. Cannot be more than 22 characters
     * or will log error and use default value instead.
     * @param string $statementDescriptor - statement descriptor to set (max of 22 character).
     * @param string $usePrefix - statement descriptor to set (max of 22 character).
     * @return self
     */
    public function setStatementDescriptor(string $statementDescriptor, bool $usePrefix = true): self
    {
        if ($usePrefix) {
            $statementDescriptor = "Minds: $statementDescriptor";
        }

        // if statement descriptor is more than 22 characters, log an error and don't set so that default is used.
        if (strlen($statementDescriptor) > 22) {
            $this->logger->error("PaymentIntent statement descriptor must be less than 22 characters: '$statementDescriptor'");
            return $this;
        }

        $this->statementDescriptor = $statementDescriptor;
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
