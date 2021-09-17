<?php
namespace Minds\Core\Rewards\Withdraw\Admin;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Rewards\Withdraw\Request;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Minds\Core\Rewards\Withdraw\Admin\Manager;
use Minds\Exceptions\UserErrorException;
use Minds\Exceptions\ServerErrorException;

/**
 * Admin Withdrawal Management Controller
 * @package Minds\Core\Rewards\Withdraw\Admin
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /** @var Config */
    protected $config;

    /**
     * Controller constructor.
     * @param Manager $manager
     */
    public function __construct(
        Manager $manager = null,
        Config $config = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Rewards\Withdraw\Admin\Manager');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Add a missing withdrawal by txid.
     * @param ServerRequest $request - request with body containing 'txid'.
     * @return JsonResponse
     */
    public function addMissingWithdrawal(ServerRequest $request): JsonResponse
    {
        $txid = $request->getParsedBody()['txid'];

        if (!$txid) {
            throw new UserErrorException('You must provide a TXID');
        }

        try {
            $this->manager->addMissingWithdrawal($txid);
        } catch (\Exception $e) {
            throw new ServerErrorException($e->getMessage());
        }

        return new JsonResponse(['status' => 'success']);
    }

    /**
     * Force pending transaction to be registered as confirmed onchain.
     * @param ServerRequest $request - request with body containing
     * 'user_guid', 'timestamp' and 'request_txid'.
     * @return JsonResponse
     */
    public function forceConfirmation(ServerRequest $request): JsonResponse
    {
        $userGuid = $request->getParsedBody()['user_guid'];
        $timestamp = $request->getParsedBody()['timestamp'];
        $requestTxid = $request->getParsedBody()['request_txid'];

        if (!$userGuid ||
            !$timestamp ||
            !$requestTxid
        ) {
            throw new UserErrorException('Missing parameters');
        }

        try {
            $requestObj = $this->manager->get(
                (new Request())
                    ->setUserGuid((string) $userGuid)
                    ->setTimestamp((int) $timestamp)
                    ->setTx((string) $requestTxid)
            );

            $this->manager->forceConfirmation($requestObj);
        } catch (\Exception $e) {
            throw new ServerErrorException($e->getMessage());
        }

        return new JsonResponse(['status' => 'success']);
    }

    /**
     * Redispatch a transaction in a completed state.
     * @param ServerRequest $request - request with body containing
     * 'user_guid', 'timestamp' and 'request_txid'.
     * @return JsonResponse
     */
    public function redispatchCompleted(ServerRequest $request): JsonResponse
    {
        $userGuid = $request->getParsedBody()['user_guid'];
        $timestamp = $request->getParsedBody()['timestamp'];
        $requestTxid = $request->getParsedBody()['request_txid'];

        if (!$userGuid ||
            !$timestamp ||
            !$requestTxid
        ) {
            throw new UserErrorException('You must provide all parameters');
        }

        try {
            $requestObj = $this->manager->get(
                (new Request())
                    ->setUserGuid((string) $userGuid)
                    ->setTimestamp((int) $timestamp)
                    ->setTx((string) $requestTxid)
            );

            $newTxid = $this->manager->redispatchCompleted($requestObj);
        } catch (\Exception $e) {
            throw new ServerErrorException($e->getMessage());
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'New transaction written: ' . $newTxid
        ]);
    }

    /**
     * Collects pending transactions older than 72 hours and fails them.
     * @return JsonResponse
     */
    public function runGarbageCollection(): JsonResponse
    {
        $this->manager->runGarbageCollection();
        return new JsonResponse(['status' => 'success']);
    }

    /**
     * Forces garbage collection / failure of a single transaction.
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function runGarbageCollectionSingle(ServerRequest $request): JsonResponse
    {
        $userGuid = $request->getParsedBody()['user_guid'];
        $timestamp = $request->getParsedBody()['timestamp'];
        $requestTxid = $request->getParsedBody()['request_txid'];

        if (!$userGuid ||
            !$timestamp ||
            !$requestTxid
        ) {
            throw new UserErrorException('You must provide all parameters');
        }

        try {
            $requestObj = $this->manager->get(
                (new Request())
                    ->setUserGuid((string) $userGuid)
                    ->setTimestamp((int) $timestamp)
                    ->setTx((string) $requestTxid)
            );

            $this->manager->runGarbageCollectionSingle($requestObj);
        } catch (\Exception $e) {
            throw new ServerErrorException($e->getMessage());
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Garbage collection success.'
        ]);
    }
}
