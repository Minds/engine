<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Utils;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Config\Config;
use Minds\Core\Payments\Manager as PaymentManager;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;

/**
 * Builds a receipts URL for a given boost.
 */
class BoostReceiptUrlBuilder
{
    private Boost $boost;

    public function __construct(
        private ?PaymentManager $paymentManager = null,
        private ?Config $config = null,
        private ?Logger $logger = null
    ) {
        $this->paymentManager ??= Di::_()->get(PaymentManager::class);
        $this->config ??= Di::_()->get('Config');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Sets instance boost.
     * @param Boost $boost - boost to set.
     * @return self
     */
    public function setBoost(Boost $boost): self
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * builds receipt url for a given boost.
     * @return string|null receipt URL for payment.
     */
    public function build(): ?string
    {
        if (!$this->boost) {
            return '';
        }
        return match ($this->boost->getPaymentMethod()) {
            BoostPaymentMethod::CASH => $this->getCashReceiptUrl(),
            BoostPaymentMethod::ONCHAIN_TOKENS => $this->getOnchainReceiptUrl(),
            default => ''
        };
    }

    /**
     * Gets onchain receipt for a given boost.
     * @return string|null onchain receipt URL for payment.
     */
    private function getOnchainReceiptUrl(): ?string
    {
        if (!$txId = $this->boost->getPaymentTxId()) {
            return null;
        }
        $baseUrl = $this->getBlockExplorerTxUrl();
        return $baseUrl . $txId;
    }

    /**
     * Gets payment receipt for a given boost.
     * @return string|null payment receipt URL.
     */
    private function getCashReceiptUrl(): ?string
    {
        try {
            $payment = $this->paymentManager->getPaymentById(
                $this->boost->getPaymentTxId()
            );
            return $payment->getReceiptUrl() ?? '';
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }

    /**
     * Gets the URL to a TX on block explorer.
     * @return string URL to a TX on block explorer.
     */
    private function getBlockExplorerTxUrl(): string
    {
        return $this->config->get('blockchain')['eth_block_explorer_tx_url'] ?? 'https://etherscan.io/tx/';
    }
}
