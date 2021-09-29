<?php

/**
 * Minds Wire Api endpoint.
 *
 * @version 2
 *
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v2;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Queue;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Util\BigNumber;
use Minds\Core\Wire\Exceptions\WalletNotSetupException;
use Minds\Entities;
use Minds\Interfaces;
use Zend\Diactoros\ServerRequestFactory;

class wire implements Interfaces\Api
{
    public function get($pages)
    {
        $response = [];

        return Factory::response($response);
    }

    /**
     * Send a wire to someone.
     *
     * @param array $pages
     *
     * API:: /v1/wire/:guid
     */
    public function post($pages)
    {
        Factory::isLoggedIn();
        $response = [];

        if (!isset($pages[0])) {
            return Factory::response(['status' => 'error', 'message' => ':guid must be passed in uri']);
        }

        $entity = Entities\Factory::build($pages[0]);

        if (!$entity) {
            return Factory::response(['status' => 'error', 'message' => 'Entity not found']);
        }

        $user = $entity->type == 'user' ? $entity : $entity->getOwnerEntity();
        if (Core\Session::getLoggedInUserGuid() === $user->guid) {
            return Factory::response(['status' => 'error', 'message' => 'You cannot send a wire to yourself!']);
        }

        $isPlus = (string) $user->getGuid() === (string) Core\Di\Di::_()->get('Config')->get('plus')['handler'];
        if (!$isPlus && !Core\Security\ACL::_()->interact($user, Core\Session::getLoggedInUser())) {
            return Factory::response(['status' => 'error', 'message' => 'You cannot send a wire to a user as you are unable to interact with them.']);
        }

        $amount = BigNumber::_($_POST['amount']);

        $recurring = isset($_POST['recurring']) ? $_POST['recurring'] : false;
        $recurringInterval = $_POST['recurring_interval'] ?? 'once';

        if ($recurring && $recurringInterval === 'once') {
            $recurringInterval = 'monthly';
            // Client side bug we need to track down, so lets log in Sentry
            \Sentry\captureMessage("Recurring Subscription was created with 'once' interval");
        }

        if (!$amount) {
            return Factory::response(['status' => 'error', 'message' => 'you must send an amount']);
        }

        if ($amount->lt(0)) {
            return Factory::response(['status' => 'error', 'message' => 'amount must be a positive number']);
        }

        $manager = Core\Di\Di::_()->get('Wire\Manager');

        $digits = 18;

        if ($_POST['method'] === 'usd') {
            $digits = 2;
        }

        /**
         * Require two factor if offchain wire
         * - TOTP priority
         * - Then SMS
         * - Then Email
         */
        if ($_POST['method'] === 'offchain') {
            try {
                $twoFactorManager = Di::_()->get('Security\TwoFactor\Manager');
                $twoFactorManager->gatekeeper(Core\Session::getLoggedinUser(), ServerRequestFactory::fromGlobals());
            } catch (\Exception $e) {
                header('HTTP/1.1 ' . $e->getCode(), true, $e->getCode());
                $response['status'] = "error";
                $response['code'] = $e->getCode();
                $response['message'] = $e->getMessage();
                $response['errorId'] = str_replace('\\', '::', get_class($e));
                return Factory::response($response);
            }
        }

        try {
            $manager
                ->setAmount((string) BigNumber::toPlain($amount, $digits))
                ->setRecurring($recurring)
                ->setRecurringInterval($recurringInterval)
                ->setSender(Core\Session::getLoggedInUser())
                ->setEntity($entity)
                ->setPayload((array) $_POST['payload']);
            $result = $manager->create();

            if (!$result) {
                throw new \Exception('Something failed');
            }

            $response['status'] = 'success';
        } catch (WalletNotSetupException $e) {
            $wireQueue = (Queue\Client::Build())
                ->setQueue('WireNotification')
                ->send([
                    'entity' => serialize($entity),
                    'walletNotSetupException' => true,
                ]);

            $response['status'] = 'error';
            $response['message'] = $e->getMessage();
        } catch (UnverifiedEmailException $e) {
            throw $e;
        } catch (\Exception $e) {
            $response['status'] = 'error';
            $response['message'] = $e->getMessage();
        }

        return Factory::response($response);
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
    }
}
