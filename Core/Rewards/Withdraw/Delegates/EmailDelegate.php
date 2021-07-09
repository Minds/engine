<?php
/**
 * Email Delegate
 */

namespace Minds\Core\Rewards\Withdraw\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Core\Util\BigNumber;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use Minds\Entities\User;

class EmailDelegate
{
    /** @var Custom */
    protected $campaign;

    public function __construct(
        $campaign = null
    ) {
        $this->campaign = $campaign ?: new Custom;
    }

    /**
     * @param Request $request
     */
    public function onRequest(Request $request): void
    {
        $subject = 'On-chain transfer request submitted';

        $amount = $this->getAmount($request);

        $this->campaign
            ->setUser(new User($request->getUserGuid()))
            ->setSubject($subject)
            ->setTemplate('withdraw-requested')
            ->setTopic('billing')
            ->setCampaign('tokens')
            ->setTitle($subject)
            ->setSignoff('Thank you,')
            ->setPreheader("Your on-chain transfer request of $amount tokens has been submitted.")
            ->setVars([
                'amount' => $amount,
            ])
            ->send();
    }

    /**
     * @param Request $request
     */
    public function onConfirm(Request $request): void
    {
        $subject = 'On-chain transfer request confirmed';

        $amount = $this->getAmount($request);

        $this->campaign
            ->setUser(new User($request->getUserGuid()))
            ->setSubject($subject)
            ->setTemplate('withdraw-confirmed')
            ->setTopic('billing')
            ->setCampaign('tokens')
            ->setTitle($subject)
            ->setSignoff('Thank you,')
            ->setPreheader("Your on-chain transfer request of $amount tokens has been confirmed.")
            ->setVars([
                'amount' => $amount,
            ])
            ->send();
    }

    /**
     * @param Request $request
     */
    public function onFail(Request $request): void
    {
        $subject = 'On-chain transfer request failed';

        $amount = $this->getAmount($request);

        $this->campaign
            ->setUser(new User($request->getUserGuid()))
            ->setSubject($subject)
            ->setTemplate('withdraw-failed')
            ->setTopic('billing')
            ->setCampaign('tokens')
            ->setTitle($subject)
            ->setSignoff('Thank you,')
            ->setPreheader("Your on-chain transfer request of $amount tokens has failed.")
            ->setVars([
                'amount' => $amount,
            ])
            ->send();
    }

    /**
     * @param Request $request
     * @throws Exception
     */
    public function onApprove(Request $request): void
    {
        $subject = 'On-chain transfer request approved';

        $amount = $this->getAmount($request);

        $this->campaign
            ->setUser(new User($request->getUserGuid()))
            ->setSubject($subject)
            ->setTemplate('withdraw-approved')
            ->setTopic('billing')
            ->setCampaign('tokens')
            ->setTitle($subject)
            ->setSignoff('Thank you,')
            ->setPreheader("Your on-chain transfer request of $amount tokens has been approved.")
            ->setVars([
                'amount' => $amount,
            ])
            ->send();
    }

    /**
     * @param Request $request
     * @throws Exception
     */
    public function onReject(Request $request): void
    {
        $subject = 'On-chain transfer request rejected';

        $amount = $this->getAmount($request);

        $this->campaign
            ->setUser(new User($request->getUserGuid()))
            ->setSubject($subject)
            ->setTemplate('withdraw-rejected')
            ->setTopic('billing')
            ->setCampaign('tokens')
            ->setTitle($subject)
            ->setSignoff('Thank you,')
            ->setPreheader("Your on-chain transfer request of $amount tokens has been rejected")
            ->setVars([
                'amount' => $amount,
            ])
            ->send();
    }


    /**
     * @param Request $request
     */
    private function getAmount(Request $request): string
    {
        return BigNumber::fromPlain($request->getAmount(), 18)->toDouble();
    }
}
