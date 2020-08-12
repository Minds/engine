<?php
namespace Minds\Core\Blockchain\Wallets;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Util\BigNumber;

class Balance
{
    /** @var OffChain\Balance */
    private $offchainBalance;

    /** @var OnChain\Balance */
    private $onchainBalance;

    /** @var User */
    private $user;

    public function __construct($offchainBalance = null, $onchainBalance = null)
    {
        $this->offchainBalance = $offchainBalance ?? Di::_()->get('Blockchain\Wallets\OffChain\Balance');
        $this->onchainBalance = $onchainBalance ?? Di::_()->get('Blockchain\Wallets\OnChain\Balance');
    }

    /**
     * Sets the user
     * @param User $user
     * @return Balance
     */
    public function setUser(User $user): Balance
    {
        $balance = clone $this;
        $balance->user = $user;
        return $balance;
    }

    /**
     * Returns combined onchain/offchain balance
     * @return BigNumber
     */
    public function get(): BigNumber
    {
        $this->onchainBalance->setUser($this->user);
        $onChainBalanceVal = BigNumber::_($this->onchainBalance->get());


        $this->offchainBalance->setUser($this->user);
        $offChainBalanceVal = BigNumber::_($this->offchainBalance->get());
        
        return $onChainBalanceVal
            ? $onChainBalanceVal->add($offChainBalanceVal)
            : $offChainBalanceVal;
    }
}
