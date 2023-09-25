<?php

namespace Minds\Controllers\Cli;

use Exception;
use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Monetization\Partners\Delegates\DepositsDelegate;
use Minds\Core\Monetization\Partners\EarningsDeposit;
use Minds\Interfaces;

class Test extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(
        private ?Logger $logger = null
    ) {
        $this->logger = Di::_()->get('Logger');
        define('__MINDS_INSTALLING__', true);
    }

    public function help($command = null): void
    {
        $this->out('TBD');
    }

    public function exec(): void
    {
        $this->logger->info('This is an INFO log message');
        $this->logger->warning('This is a WARNING log message');
        $this->logger->debug('This is a DEBUG log message');
        $this->logger->alert('This is an ALERT log message');
        $this->logger->error('This is an ERROR log message');
        $this->logger->critical('This is a CRITICAL log message');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testAffiliateDepositNotifications(): void
    {
        $affiliateUserGuid = $this->getOpt('affiliate-guid') ?: "1508488626269327366";
        $deposit = (new EarningsDeposit())
            ->setTimestamp(time())
            ->setUserGuid($affiliateUserGuid)
            ->setAmountCents(100)
            ->setItem('affiliate');

        $affiliateUser = (Di::_()->get('EntitiesBuilder'))->single($affiliateUserGuid);

        (new DepositsDelegate)->onIssueAffiliateDeposit($affiliateUser, $deposit);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testReferrerAffiliateDepositNotifications(): void
    {
        $referrerUserGuid = $this->getOpt('referrer-guid') ?: "1508488626269327366";
        $deposit = (new EarningsDeposit())
            ->setTimestamp(time())
            ->setUserGuid($referrerUserGuid)
            ->setAmountCents(50)
            ->setItem('affiliate_referrer');

        $referrerUser = (Di::_()->get('EntitiesBuilder'))->single($referrerUserGuid);

        (new DepositsDelegate)->onIssueAffiliateReferrerDeposit($referrerUser, $deposit);
    }
}
