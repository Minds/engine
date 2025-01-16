<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Onchain;

use Minds\Core\Blockchain\Services\Ethereum as EthereumService;
use Minds\Core\Boost\V3\Enums\BoostAdminAction;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Config\Config;
use Minds\Core\Util\BigNumber;
use Minds\Core\Di\Di;
use Minds\Exceptions\ServerErrorException;

/**
 * Processes interactions with boost contract from an admin,
 * such that they can act upon onchain boost.
 */
class AdminTransactionProcessor
{
    public function __construct(
        private ?EthereumService $ethereumService = null,
        private ?Config $config = null,
    ) {
        $this->ethereumService ??= Di::_()->get('Blockchain\Services\Ethereum');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Will send a boost transaction if actionable.
     * @param Boost $boost - boost to act upon.
     * @param int $action - action to perform on boost
     * @param array $data - data to submit to contract.
     * @param int $gasLimit - gas limit to use to send.
     * @throws Exception - if an exception occurs.
     * @throws ServerErrorException - if there is an amount mismatch between blockchain and server.
     * @return string txid if one is present.
     */
    public function send(Boost $boost, int $action, int $gasLimit = 200000): string
    {
        if ($this->isActionable($boost)) {
            $config = $this->getBoostContractConfig();
            return $this->ethereumService->sendRawTransaction($config['wallet_pkey'], [
                'from' => $config['wallet_address'],
                'to' => $config['contract_address'],
                'startGas' => BigNumber::_($gasLimit)->toHex(true),
                'gasLimit' => BigNumber::_($gasLimit)->toHex(true),
                'data' => $this->ethereumService->encodeContractMethod(
                    contractMethodDeclaration: $this->getContractMethodDeclaration($action),
                    params: [ BigNumber::_($boost->getGuid())->toHex(true) ]
                ),
                'value' => '0x0',
            ]);
        }
        return '';
    }

    /**
     * Whether a boost is actionable based upon whether is is confirmed onchain and the boost GUID for the transaction
     * matches the expected GUID.
     * @param Boost $boost - boost to check is actionable.
     * @throws ServerErrorException - if there is an amount mismatch between blockchain and server.
     * @return bool true if boost is actionable.
     */
    private function isActionable(Boost $boost): bool
    {
        $receipt = $this->ethereumService->request('eth_getTransactionReceipt', [ $boost->getPaymentTxId() ]);

        if (!$receipt || !isset($receipt['status'])) {
            return false; // Boost request has not yet been confirmed onchain.
        }

        if ($receipt['status'] === '0x1') {
            $blockchainAmount = BigNumber::fromHex($receipt['logs'][1]['data']);
            $blockchainAmountDouble = BigNumber::fromPlain($blockchainAmount, 18)->toDouble();
            $serverAmountDouble = BigNumber::_($boost->getPaymentAmount())->toDouble();

            if ($serverAmountDouble !== $blockchainAmountDouble) {
                throw new ServerErrorException('Amount mismatch between blockchain and server');
            }

            $guid = (string) BigNumber::fromHex($receipt['logs'][3]['data']);
            return $boost->getGuid() === $guid;
        }

        return false;
    }

    /**
     * Get contract method declaration for given action.
     * @param int $action - action to get declaration for.
     * @throws ServerErrorException - if action is invalid.
     * @return string - contract method declaration for given action.
     */
    private function getContractMethodDeclaration(int $action): string
    {
        return match ($action) {
            BoostAdminAction::ACCEPT => 'accept(uint256)',
            BoostAdminAction::REJECT => 'reject(uint256)',
            default => throw new ServerErrorException("Not yet implemented boost contract action")
        };
    }

    /**
     * Get config for boost contract.
     * @return array config for boost contract.
     */
    private function getBoostContractConfig(): array
    {
        return $this->config->get('blockchain')['contracts']['boost'];
    }
}
