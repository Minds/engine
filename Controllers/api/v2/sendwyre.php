<?php
/**
 * Minds SendWyre Api endpoint.
 * @author Ben Hayward
 */
namespace Minds\Controllers\api\v2;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Interfaces;
use Minds\Core\Di\Di;

class sendwyre implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * POST api/v2/sendwyre - create a wallet order reservation and retrieve link.
     *
     * @param string $dest
     * @param string $destCurrency
     * @param string $sourceCurrency
     * @param string $amount
     *
     * @return Object - containing status, url (on success), message (on error).
     */
    public function post($pages)
    {
        // params
        $dest = $_POST['dest'] ?? null;
        $destCurrency = $_POST['destCurrency'] ?? null;
        $sourceCurrency = $_POST['sourceCurrency'] ?? null;
        $amount = $_POST['amount'] ?? null;

        // validation
        if (
            $dest === null || $destCurrency === null ||
            $sourceCurrency === null || $amount === null
        ) {
            return Factory::response([
                'status' => 422,
                'message' => 'Missing required parameters to communicate with SendWyre',
            ]);
        }

        $sendWyreConfig = Di::_()->get('Config')->get('sendwyre');

        // assemble data
        $data = [
            'dest' => $dest,
            'destCurrency' => $destCurrency,
            'sourceCurrency' => $sourceCurrency,
            'amount' => $amount,
            'redirectUrl' => $sendWyreConfig['redirectUrl'],
            'failureRedirectUrl' => $sendWyreConfig['failureRedirectUrl'],
            'referrerAccountId' => $sendWyreConfig['accountId'],
        ];

        try {
            // post to SendWyre.
            $response = Di::_()->get('Http')->post($sendWyreConfig['baseUrl'].'v3/orders/reserve', $data, [
                'headers' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$sendWyreConfig['secretKey'],
                ]
            ]);

            return Factory::response([
                'status' => 'success',
                'url' => json_decode($response, JSON_UNESCAPED_SLASHES)['url']
            ]);
        } catch (\Exception $e) {
            Core\Di\Di::_()->get('Logger')->error($e);

            return Factory::response([
                'status' => 'error',
            ]);
        }
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
