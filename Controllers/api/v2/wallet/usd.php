<?php
/**
 * USD Wallet Controller
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v2\wallet;

use Minds\Core;
use Minds\Helpers;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Payments;
use Minds\Entities;

class usd implements Interfaces\Api
{
    /**
     * Returns merchant information
     * @param array $pages
     *
     * API:: /v1/merchant/:slug
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        $response = [];

        switch ($pages[0]) {
            case "status":
                $merchants = Core\Di\Di::_()->get('Monetization\Merchants');
                $merchants->setUser(Core\Sandbox::user(Core\Session::getLoggedInUser(), 'merchant'));

                $isMerchant = (bool) $merchants->getId();
                $canBecomeMerchant = !$merchants->isBanned();

                return Factory::response([
                    'isMerchant' => $isMerchant,
                    'canBecomeMerchant' => $canBecomeMerchant,
                ]);
                break;
            case "settings":
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

                break;
        }

        return Factory::response($response);
    }

    public function post($pages)
    {
        Factory::isLoggedIn();

        $response = array();

        switch ($pages[0]) {
            case "onboard":
                $account = (new Payments\Stripe\Connect\Account())
                    ->setUserGuid(Core\Session::getLoggedInUser()->guid)
                    ->setUser(Core\Session::getLoggedInUser())
                    ->setDestination('bank')
                    ->setCountry($_POST['country'])
                    ->setSSN($_POST['ssn'] ? str_pad((string) $_POST['ssn'], 4, '0', STR_PAD_LEFT) : '')
                    ->setPersonalIdNumber($_POST['personalIdNumber'])
                    ->setFirstName($_POST['firstName'])
                    ->setLastName($_POST['lastName'])
                    ->setGender($_POST['gender'])
                    ->setDateOfBirth($_POST['dob'])
                    ->setStreet($_POST['street'])
                    ->setCity($_POST['city'])
                    ->setState($_POST['state'])
                    ->setPostCode($_POST['postCode'])
                    ->setPhoneNumber($_POST['phoneNumber'])
                    ->setIp($_SERVER['HTTP_X_FORWARDED_FOR']);

                try {
                    $stripeConnectManager = Core\Di\Di::_()->get('Stripe\Connect\Manager');
                    $id = $stripeConnectManager->add($account);

                } catch (\Exception $e) {
                    $response['status'] = "error";
                    $response['message'] = $e->getMessage();
                }

                break;
            case "verification":
                try {
                    $stripe = Core\Di\Di::_()->get('StripePayments');
                    $stripe->verifyMerchant(Core\Session::getLoggedInUser()->getMerchant()['id'], $_FILES['file']);
                } catch (\Exception $e) {
                    $response['status'] = "error";
                    $response['message'] = $e->getMessage();
                }
                break;
            case "update":
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
                break;
        }

        return Factory::response($response);
    }

    public function put($pages)
    {
        return Factory::response(array());
    }

    public function delete($pages)
    {
        return Factory::response(array());
    }

}
