<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2;

use Iterator;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\V2\Enums\PaymentStatus;
use Minds\Core\Payments\V2\Exceptions\PaymentNotFoundException;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class Repository
{
    /** @var int */
    const AFFILIATE_SHARE_PCT = 45; // 45%

    private PDO $mysqlClientReader;
    private PDO $mysqlClientWriter;
    private Connection $mysqlClientWriterHandler;
    private Connection $mysqlClientReaderHandler;

    /**
     * @param MySQLClient|null $mysqlHandler
     * @param Logger|null $logger
     * @throws ServerErrorException
     */
    public function __construct(
        private ?MySQLClient $mysqlHandler = null,
        private ?Logger $logger = null
    ) {
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");

        $this->mysqlClientReader = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);
        $this->mysqlClientReaderHandler = new Connection($this->mysqlClientReader);

        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER);
        $this->mysqlClientWriterHandler = new Connection($this->mysqlClientWriter);

        $this->logger = Di::_()->get('Logger');
    }

    /**
     * Creates a new Minds payment record
     * @param PaymentDetails $paymentDetails
     * @return void
     * @throws ServerErrorException
     */
    public function createPayment(PaymentDetails $paymentDetails): void
    {
        $statement = $this->mysqlClientWriterHandler->insert()
            ->into('minds_payments')
            ->set([
                'payment_guid' => new RawExp(':payment_guid'),
                'user_guid' => new RawExp(':user_guid'),
                'affiliate_user_guid' => new RawExp(':affiliate_user_guid'),
                'affiliate_type' => new RawExp(':affiliate_type'),
                'payment_type' => new RawExp(':payment_type'),
                'payment_method' => new RawExp(':payment_method'),
                'payment_amount_millis' => new RawExp(':payment_amount_millis'),
                'payment_tx_id' => new RawExp(':payment_tx_id'),
                'is_captured' => new RawExp(':is_captured'),
                'payment_status' => new RawExp(':payment_status'),
            ])
            ->prepare();

        $values = [
            'payment_guid' => $paymentDetails->paymentGuid,
            'user_guid' => $paymentDetails->userGuid,
            'affiliate_user_guid' => $paymentDetails->affiliateUserGuid,
            'affiliate_type' => $paymentDetails->affiliateType,
            'payment_type' => $paymentDetails->paymentType,
            'payment_method' => $paymentDetails->paymentMethod,
            'payment_amount_millis' => $paymentDetails->paymentAmountMillis,
            'payment_tx_id' => $paymentDetails->paymentTxId,
            'payment_status' => $paymentDetails->paymentStatus ?: PaymentStatus::PENDING,
            'is_captured' => $paymentDetails->isCaptured
        ];

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        try {
            $statement->execute();
        } catch (PDOException $e) {
            $this->logger->error(
                "An issue was encountered whilst storing the payment information",
                [
                    'query' => $statement->queryString,
                    'values' => $values,
                    'paymentDetails' => $paymentDetails->export(),
                    'errorMessage' => $e->getMessage(),
                    'stackTrace' => $e->getTraceAsString(),
                ]
            );
            throw new ServerErrorException("An issue was encountered whilst storing the payment information");
        }
    }

    /**
     * @param PaymentDetails $paymentDetails
     * @return void
     * @throws ServerErrorException
     * @throws PaymentNotFoundException
     */
    public function updatePayment(PaymentDetails $paymentDetails): void
    {
        $statement = $this->mysqlClientWriterHandler->update()
            ->table('minds_payments')
            ->set([
                'payment_status' => new RawExp(':payment_status'),
                'updated_timestamp' => date('c', time()),
            ])
            ->where('payment_guid', Operator::EQ, $paymentDetails->paymentGuid)
            ->prepare();

        $values = [
            'payment_status' => $paymentDetails->paymentStatus
        ];

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        try {
            $statement->execute();

            if ($statement->rowCount() === 0) {
                throw new PaymentNotFoundException();
            }
        } catch (PDOException $e) {
            $this->logger->error(
                "An issue was encountered whilst updating the payment information",
                [
                    'query' => $statement->queryString,
                    'values' => $values,
                    'paymentDetails' => $paymentDetails->export(),
                    'errorMessage' => $e->getMessage(),
                    'stackTrace' => $e->getTraceAsString(),
                ]
            );
            throw new ServerErrorException("An issue was encountered whilst updating the payment information");
        }
    }

    /**
     * @param int $paymentGuid
     * @param int $paymentStatus
     * @param bool $isCaptured
     * @return void
     * @throws PaymentNotFoundException
     * @throws ServerErrorException
     */
    public function updatePaymentStatus(int $paymentGuid, int $paymentStatus, bool $isCaptured = false): void
    {
        $statement = $this->mysqlClientWriterHandler->update()
            ->table('minds_payments')
            ->set([
                'payment_status' => new RawExp(':payment_status'),
                'is_captured' => new RawExp(':is_captured'),
                'updated_timestamp' => date('c', time()),
            ])
            ->where('payment_guid', Operator::EQ, $paymentGuid)
            ->prepare();

        $values = [
            'payment_status' => $paymentStatus,
            'is_captured' => $isCaptured,
        ];

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        try {
            $statement->execute();

            if ($statement->rowCount() === 0) {
                throw new PaymentNotFoundException();
            }
        } catch (PDOException $e) {
            $this->logger->error(
                "An issue was encountered whilst updating the payment information",
                [
                    'query' => $statement->queryString,
                    'values' => $values,
                    'errorMessage' => $e->getMessage(),
                    'stackTrace' => $e->getTraceAsString(),
                ]
            );
            throw new ServerErrorException("An issue was encountered whilst updating the payment information");
        }
    }

    /**
     * Returns affiliates earnings
     * @throws ServerErrorException
     */
    public function getPaymentsAffiliatesEarnings(PaymentOptions $options): Iterator
    {
        $sharePct = self::AFFILIATE_SHARE_PCT / 100;

        $paymentFeePct = 0.029; // 2.9%
        $paymentFeeMillis = 300; // $0.30

        $values = [];
        $statement = $this->mysqlClientReaderHandler->select()
            ->columns([
                'affiliate_user_guid',
                'total_earnings_millis' => new RawExp("SUM((payment_amount_millis - ((payment_amount_millis * $paymentFeePct) - $paymentFeeMillis)) * $sharePct)")
            ])
            ->from('minds_payments')
            ->where('updated_timestamp', Operator::GTE, date('c', $options->getFromTimestamp()))
            // Exclude gift card payments (the lazy way - ie. should do left join on gift tx table)
            ->where('payment_tx_id', Operator::IS_NOT, null);

        if ($options->getWithAffiliate()) {
            if ($options->getAffiliateGuid()) {
                $statement->where('affiliate_user_guid', Operator::EQ, new RawExp(':affiliate_user_guid'));
                $values['affiliate_user_guid'] = $options->getAffiliateGuid();
            } else {
                $statement->where('affiliate_user_guid', Operator::IS_NOT, null);
            }
            $statement->where('affiliate_user_guid', Operator::NOT_EQ, new RawExp('user_guid')); // Do not allow affiliate to be the spender
        }

        if ($options->getPaymentMethod()) {
            $statement->where('payment_method', Operator::EQ, new RawExp(':payment_method'));
            $values['payment_method'] = $options->getPaymentMethod();
        }

        if ($options->getPaymentStatus()) {
            $statement->where('payment_status', Operator::EQ, new RawExp(':payment_status'));
            $values['payment_status'] = $options->getPaymentStatus();
        }

        if (count($options->getPaymentTypes()) > 0) {
            $statement->whereWithNamedParameters(
                leftField: 'payment_type',
                operator: Operator::IN,
                parameterName: 'payment_type',
                totalParameters: count($options->getPaymentTypes())
            );
            $values['payment_type'] = $options->getPaymentTypes();
        }

        if ($options->getToTimestamp()) {
            $statement->where('updated_timestamp', Operator::LTE, date('c', $options->getToTimestamp()));
        }

        $statement = $statement->groupBy('affiliate_user_guid')
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        try {
            $statement->execute();

            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $affiliate) {
                yield $affiliate;
            }
        } catch (PDOException $e) {
            throw new ServerErrorException("An error occurred whilst retrieving affiliates earnings");
        }
    }
}
