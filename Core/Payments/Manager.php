<?php

/**
 * Payments Manager
 *
 * @author emi
 */

namespace Minds\Core\Payments;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Models\GetPaymentsOpts;
use Minds\Core\Payments\Models\Payment;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Minds\Helpers\Cql;

class Manager
{
    /** @var string $type */
    protected $type;

    /** @var integer|string $user_guid */
    protected $user_guid;

    /** @var integer $time_created */
    protected $time_created;

    /** @var string $payment_id */
    protected $payment_id;

    /** @var Repository $repository */
    protected $repository;

    public function __construct(
        $repository = null,
        private ?IntentsManagerV2 $intentsManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Logger $logger = null
    ) {
        $this->repository = $repository ?: Di::_()->get('Payments\Repository');
        $this->intentsManager ??= new IntentsManagerV2();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getUserGuid()
    {
        return $this->user_guid;
    }

    /**
     * @param int|string $user_guid
     */
    public function setUserGuid($user_guid)
    {
        $this->user_guid = $user_guid;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeCreated()
    {
        return $this->time_created;
    }

    /**
     * @param int $time_created
     */
    public function setTimeCreated($time_created)
    {
        $this->time_created = $time_created;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentId()
    {
        return $this->payment_id;
    }

    /**
     * @param string $payment_id
     */
    public function setPaymentId($payment_id)
    {
        $this->payment_id = $payment_id;
        return $this;
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function create(array $data)
    {
        if (!$this->getType()) {
            throw new \Exception('Type is required');
        }

        if (!$this->getUserGuid()) {
            throw new \Exception('User GUID is required');
        }

        if (!$this->getTimeCreated()) {
            throw new \Exception('Time created is required');
        }

        if (!$this->getPaymentId()) {
            $this->setPaymentId('guid:' . Guid::build());
        }

        $result = $this->repository->upsert(
            $this->getType(),
            $this->getUserGuid(),
            $this->getTimeCreated(),
            $this->getPaymentId(),
            $data
        );

        if (!$result) {
            throw new \Exception('Cannot save payment');
        }

        return $this->getPaymentId();
    }

    /**
     * @param array $data
     * @return string|bool
     * @throws \Exception
     */
    public function updatePaymentById(array $data)
    {
        $row = $this->repository->getByPaymentId($this->getPaymentId());

        if (!$row) {
            return false;
        }

        $row = Cql::toPrimitiveType($this->repository->getByPaymentId($this->getPaymentId()));

        $this
            ->setType($row['type'])
            ->setUserGuid($row['user_guid'])
            ->setTimeCreated($row['time_created']);

        $result = $this->repository->upsert(
            $this->getType(),
            $this->getUserGuid(),
            $this->getTimeCreated(),
            $this->getPaymentId(),
            $data
        );

        if (!$result) {
            throw new \Exception('Cannot update payment');
        }

        return $this->getPaymentId();
    }

    /**
     * Get an individual payment by paymentId.
     * @param string $paymentId - payment id to get payment for.
     * @return Payment payment model.
     */
    public function getPaymentById(string $paymentId): Payment
    {
        try {
            $paymentIntent = $this->intentsManager->getPaymentIntentByPaymentId($paymentId);
            return $this->buildPaymentFromData($paymentIntent);
        } catch (\Exception $e) {
            $this->logger->error($e);
            throw new NotFoundException('Could not find payment');
        }
    }

    /**
     * Get payments for a user.
     * @param GetPaymentsOpts $opts - opts to get payments with. If set, user id will be ignored
     * and replaced with instance users guid.
     * @throws ServerErrorException - if there is a server error while getting payments.
     * @return array array containing data on payments, and whether there are more to get.
     */
    public function getPayments(GetPaymentsOpts $opts): array
    {
        try {
            $paymentIntents = $this->intentsManager->getPaymentIntentsByUserGuid(
                userGuid: $this->user_guid,
                opts: $opts
            );
            if (!count($paymentIntents['data'])) {
                $this->logger->warning("Customer not found: {$this->user_guid}");
                return [];
            }
        } catch (UserErrorException $e) {
            $this->logger->warning($e->getMessage());
            return [];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new ServerErrorException('An error occurred while getting your payments');
        }

        $payments = [];
        foreach ($paymentIntents['data'] as $paymentIntent) {
            $payments[] = $this->buildPaymentFromData($paymentIntent);
        }

        return [
            'has_more' => $paymentIntents['has_more'],
            'data' => $payments
        ];
    }

    /**
     * Build a payment from payment data.
     * @param array $paymentIntent - data to build from.
     * @return Payment - payment model.
     */
    private function buildPaymentFromData(array $paymentIntent): Payment
    {
        $charge = $this->selectPrimaryCharge($paymentIntent['charges']['data']);
        $recipient = $this->buildRecipient($paymentIntent);
        $sender = $this->buildSender($paymentIntent);

        return Payment::fromData([
            'status' => $paymentIntent['status'],
            'payment_id' => $paymentIntent['id'],
            'currency' => $paymentIntent['currency'],
            'minor_unit_amount' => $paymentIntent['amount'],
            'statement_descriptor' => $paymentIntent['statement_descriptor'],
            'receipt_url' => $charge['receipt_url'],
            'created_timestamp' => $paymentIntent['created'],
            'recipient' => $recipient,
            'sender' => $sender
        ]);
    }

    /**
     * Select primary charge from charges array. Will take the succeeded charge first.
     * If one does not exist, will take the last charge made.
     * @param array $charges - charges to check.
     * @return array|null - primary charge.
     */
    private function selectPrimaryCharge(array $charges): ?array
    {
        if (!count($charges)) {
            return null;
        }

        $successfulCharge = array_values(array_filter($charges, function ($charge) {
            return $charge['status'] === 'succeeded';
        }))[0] ?? false;

        if ($successfulCharge) {
            return $successfulCharge;
        }

        return end($charges);
    }

    /**
     * Build recipient from Stripe PaymentIntent data.
     * @param array $data - Stripe PaymentIntent data.
     * @return User|null - recipient user if one can be established, else null.
     */
    private function buildRecipient(array $data): ?User
    {
        if (isset($data['metadata']) && isset($data['metadata']['receiver_guid'])) {
            return $this->entitiesBuilder->single(
                $data['metadata']['receiver_guid']
            ) ?? null;
        }
        return null;
    }

    /**
     * Build sender from Stripe PaymentIntent data.
     * @param array $data - Stripe PaymentIntent data.
     * @return User|null - sender user if one can be established, else null.
     */
    private function buildSender(array $data): ?User
    {
        if (isset($data['metadata'])) {
            if (isset($data['metadata']['user_guid'])) {
                return $this->entitiesBuilder->single(
                    $data['metadata']['user_guid']
                ) ?? null;
            }

            if (isset($data['metadata']['boost_sender_guid'])) {
                return $this->entitiesBuilder->single(
                    $data['metadata']['boost_sender_guid']
                ) ?? null;
            }
        }
        return null;
    }
}
