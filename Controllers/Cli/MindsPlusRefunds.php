<?php

namespace Minds\Controllers\Cli;

use Cassandra\Bigint;
use Cassandra\PreparedStatement;
use Cassandra\Varint;
use Iterator;
use League\Csv\Exception;
use League\Csv\Reader as CsvReader;
use Minds\Cli\Controller;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomPreparedStatement;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\MindsPlusRefunds as MindsPlusRefundsAlias;
use Minds\Core\Security\ACL;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Interfaces\CliControllerInterface;

class MindsPlusRefunds extends Controller implements CliControllerInterface
{
    public function __construct(
        private ?OffchainTransactions $offchainTransactions = null,
        private  ?CassandraClient $cassandraClient = null
    ) {
        $this->offchainTransactions ??= Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli.php minds_plus_refunds <action>');
    }

    public function exec()
    {
        $this->processRefunds();
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function processRefunds()
    {
        $this->out("Script to process Minds+ refunds\n");

        $dataReader = CsvReader::createFromPath(__MINDS_ROOT__ . "/" . $this->getOpt("file"), 'r');
        $dataReader->setHeaderOffset(0);

        $records = $dataReader->getRecords(["source", "target", "amount", "last_billing_date", "yearly"]);

        ACL::_()->setIgnore(true);
        $this->runRefundsLogic($records);
    }

    /**
     * @throws \Exception
     */
    private function runRefundsLogic(Iterator $records): void
    {
        foreach ($records as $record) {
            $sourceUserGuid = $record['source'];
            $targetUserGuid = $record['target'];
            $amountToRefund = (int)($record['amount'] / 1000000000000000000);
            $isYearly = (bool)$record['yearly'];
            $lastBillingDate = $record['last_billing_date'];
            $months = null;

            $sourceUser = Di::_()->get('EntitiesBuilder')->single($sourceUserGuid);
            $targetUser = Di::_()->get('EntitiesBuilder')->single($targetUserGuid);

            if (!$targetUser) {
                continue;
            }

            if (!$isYearly) {
                $months = $this->calculateMonths($lastBillingDate);
                if ($months < 1) {
                    $months = 1;
                }
                $amountToRefund *= $months;
            }

            $execute = true;

            if (!$this->canRefundBeCompleted($amountToRefund, $targetUser)) {
                $execute = false;
            }

            if ($this->getOpt("dry-run") && $targetUserGuid !== '100000000000000063') {
                $this->printRefundDetails(
                    $targetUserGuid,
                    $targetUser->getGuid(),
                    $targetUser->getEmail() ?: '',
                    $isYearly,
                    $months,
                    $amountToRefund,
                    $execute
                );

                continue;
            }

            if (!$execute) {
                continue;
            }

            $this->transferFunds($targetUser, $sourceUser, $amountToRefund);

            $this->give1YearMindsPlus($targetUser);

            $this->sendRefundEmail($targetUser);
        }
    }

    private function give1YearMindsPlus(User $target): void
    {
        $channelsManager = Di::_()->get('Channels\Manager');
        $channelsManager->flushCache($target);

        if (!$target->guid || $target->getType() !== 'user') {
            return;
        }

        $target->setPlusExpires(
            strtotime("+1 year", time())
        );

        $isAllowed = ACL::_()->setIgnore(true);

        $target->save();
        ACL::_()->setIgnore($isAllowed);
    }

    /**
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws \Exception
     */
    private function transferFunds(
        User $targetUser,
        User $sourceUser,
        int $amount
    ): bool {
        $amount = BigNumber::toPlain($amount, 18);
        $this->offchainTransactions
            ->setType('refund')
            ->setUser($targetUser)
            ->setAmount((string) $amount)
            ->transferFrom($sourceUser);
        return true;
    }

    private function sendRefundEmail(User $targetUser): void
    {
        $email = (new \Minds\Core\Email\V2\Campaigns\Custom\Custom())
            ->setUser($targetUser)
            ->setSubject("Update regarding your Minds+ membership")
            ->setTemplate("minds-plus-refunds");

        $email->send();
    }

    private function printRefundDetails(
        string $targetUserGuid,
        string $username,
        string $email,
        bool $isYearly,
        ?int $months,
        int $amountToRefund,
        bool $execute
    ): void {
        $this->out([
            "------------------------------------",
            "Refund details\n",
            "Target User GUID: $targetUserGuid",
            "Username: {$username}",
            "Email: {$email}",
            "Charged Yearly: " . ($isYearly ? "yes" : "no"),
            "Months charged: " . ($months ?? "N/A"),
            "Amount to Refund: {$amountToRefund}",
            "Refund already processed: " . (!$execute ? "yes" : "no") . "\n",
        ]);
    }

    /**
     * @throws \Exception
     */
    private function canRefundBeCompleted(
        int $amountToRefund,
        User $targetUser
    ): bool {
        $statement = "SELECT
                tx
            FROM
                blockchain_transactions_mainnet
            WHERE
                user_guid = ? AND amount = ? AND contract = ?
            ALLOW FILTERING";

        $values = [
            new Varint($targetUser->getGuid()),
            new Varint((string)BigNumber::toPlain($amountToRefund, 18)),
            "offchain:refund"
        ];

        $query = $this->prepareCassandraQuery($statement, $values);

        $rows = $this->cassandraClient?->request($query);

        return !$rows || $rows->count() == 0;
    }

    /**
     * @param $statement
     * @param $values
     * @return CustomPreparedStatement
     */
    private function prepareCassandraQuery($statement, $values): CustomPreparedStatement
    {
        return (new CustomPreparedStatement())
            ->query($statement, $values);
    }

    private function calculateMonths(string $lastBillingDate): int
    {
        $startDate = strtotime("2021-03-01 00:00:00");
        $endDate = strtotime($lastBillingDate);

        $totalSecondsDiff = abs($endDate - $startDate);

        return round($totalSecondsDiff/60/60/24/30, 0, PHP_ROUND_HALF_UP);
    }
}
