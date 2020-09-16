<?php
/**
 * USD Wallet Controller
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v2\wallet\usd;

use Minds\Core;
use Minds\Core\Config;
use Minds\Helpers;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Payments;
use Minds\Entities;

class account implements Interfaces\Api
{
    /**
     * @param array $pages
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        $response = [];

        $stripeConnectManager = Core\Di\Di::_()->get('Stripe\Connect\Manager');
        try {
            $account = $stripeConnectManager->getByUser(Core\Session::getLoggedInUser());
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }

        if (!$account) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Not a merchant account'
            ]);
        }

        $response['account'] = $account->export();

        return Factory::response($response);
    }

    /**
     * @param array $pages
     */
    public function post($pages)
    {
        Factory::isLoggedIn();

        $response = [];

        $account = (new Payments\Stripe\Connect\Account())
            ->setId(Core\Session::getLoggedInUser()->getMerchant()['id'])
            ->setFirstName($_POST['firstName'])
            ->setLastName($_POST['lastName'])
            ->setGender($_POST['gender'])
            ->setDateOfBirth($_POST['dob'])
            ->setStreet($_POST['street'])
            ->setCity($_POST['city'])
            ->setState($_POST['state'])
            ->setPostCode($_POST['postCode'])
            ->setPhoneNumber($_POST['phoneNumber']);

        if ($_POST['ssn']) {
            $account->setSSN($_POST['ssn'] ? str_pad((string) $_POST['ssn'], 4, '0', STR_PAD_LEFT) : '');
        }

        if ($_POST['personalIdNumber']) {
            $account->setPersonalIdNumber($_POST['personalIdNumber']);
        }

        if ($_POST['accountNumber']) {
            $account->setAccountNumber($_POST['accountNumber']);
        }

        if ($_POST['routingNumber']) {
            $account->setRoutingNumber($_POST['routingNumber']);
        }

        try {
            $stripeConnectManager = Core\Di\Di::_()->get('Stripe\Connect\Manager');
            $result = $stripeConnectManager->update($account);
        } catch (\Exception $e) {
            $response['status'] = "error";
            $response['message'] = $e->getMessage();
        }

        return Factory::response($response);
    }

    /**
     * @param array $pages
     */
    public function put($pages)
    {
        Factory::isLoggedIn();
        $response = [];

        $vars = Core\Router\PrePsr7\Router::getPutVars();

        $user = Core\Session::getLoggedInUser();

        $account = (new Payments\Stripe\Connect\Account())
            ->setUserGuid($user->guid)
            ->setUser($user)
            ->setDestination('bank')
            ->setCountry($vars['country'])
            ->setSSN($vars['ssn'] ? str_pad((string) $vars['ssn'], 4, '0', STR_PAD_LEFT) : '')
            ->setPersonalIdNumber($vars['personalIdNumber'])
            ->setFirstName($vars['firstName'])
            ->setLastName($vars['lastName'])
            ->setGender($vars['gender'])
            ->setDateOfBirth($vars['dob'])
            ->setStreet($vars['street'])
            ->setCity($vars['city'])
            ->setState($vars['state'])
            ->setPostCode($vars['postCode'])
            ->setPhoneNumber($vars['phoneNumber'])
            ->setIp($_SERVER['HTTP_X_FORWARDED_FOR'])
            ->setEmail($user->getEmail())
            ->setUrl(Config::_()->get('site_url') . $user->username)
            ->setMetadata([
                'user_guid' => $user->guid
            ]);

        if ($vars['country'] === 'IN') {
            $account->setPayoutInterval('daily');
        }

        try {
            $stripeConnectManager = Core\Di\Di::_()->get('Stripe\Connect\Manager');
            $account = $stripeConnectManager->add($account);
            $response['account'] = $account->export();
        } catch (\Exception $e) {
            $response['status'] = "error";
            $response['message'] = $e->getMessage();
        }

        return Factory::response($response);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
