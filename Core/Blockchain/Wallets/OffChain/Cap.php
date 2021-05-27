<?php

/**
 * OffChain Wallet Cap
 *
 * @author emi
 */

namespace Minds\Core\Blockchain\Wallets\OffChain;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;

class Cap
{
    // Maximum withdrawable in a month by a user in Gwei
    const MAXIMUM_SINGLE_USER_MONTHLY_THRESHOLD = 10000000000000000000;

    /** @var Config */
    protected $config;

    /** @var Balance */
    protected $offChainBalance;

    /** @var User */
    protected $user;

    /** @var string */
    protected $contract;

    public function __construct($config = null, $offchainBalance = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
        $this->offChainBalance = $offchainBalance ?: Di::_()->get('Blockchain\Wallets\OffChain\Balance');
    }

    /**
     * @param int|User $user
     * @return Cap
     */
    public function setUser($user)
    {
        if (is_numeric($user)) {
            $user = new User($user);
        }

        $this->user = $user;
        return $this;
    }

    /**
     * @param string $contract
     * @return Cap
     */
    public function setContract($contract)
    {
        $this->contract = $contract;
        return $this;
    }

    /**
     * Returns the amount of tokens a user is allowed to spend
     * @return string
     * @throws \Exception
     */
    public function allowance()
    {
        $contract = 'offchain:' . $this->contract;

        $this->offChainBalance->setUser($this->user);
        $offChainBalanceVal = $this->offChainBalance->getByContract($contract, strtotime('today 00:00'), true);

        $todaySpendBalance = BigNumber::_($offChainBalanceVal)->neg();
        $cap = BigNumber::toPlain($this->config->get('blockchain')['offchain']['cap'] ?: 0, 18)->sub($todaySpendBalance);

        return (string) $cap;
    }

    /**
     * Returns true if a user has exceeded the offchain single user cap.
     * @return bool
     */
    public function exceedsSingleUserCap($receiver): bool
    {
        return $this->offChainBalance
            ->setUser($this->user)
            ->countByReceiver(strtotime('-1 month'), $receiver)
            ->gte($this->MAXIMUM_SINGLE_USER_MONTHLY_THRESHOLD);
    }

    /**
     * Returns true if the user is allowed to spend amount.
     * @param $amount
     * @return bool
     * @throws \Exception
     */
    public function isAllowed($amount)
    {
        return BigNumber::_($this->allowance())->gte($amount);
    }
}
