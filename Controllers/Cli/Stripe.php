<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Entities;
use Minds\Core\Payments\Models\GetPaymentsOpts;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Exceptions\UserErrorException;

class Stripe extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(
        private ?IntentsManagerV2 $intentsManager = null
    ) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->intentsManager ??= new IntentsManagerV2();
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        echo "1";
    }

    public function get_payment_intent()
    {
        $intent = new Core\Payments\Stripe\Intents\PaymentIntent();
        $intent->setAmount(2000);

        $intentManager = new Core\Payments\Stripe\Intents\Manager();
        $intent = $intentManager->add($intent);

        var_dump($intent);
    }

    public function get_setup_intent()
    {
        $intent = new Core\Payments\Stripe\Intents\SetupIntent();

        $intentManager = new Core\Payments\Stripe\Intents\Manager();
        $intent = $intentManager->add($intent);

        var_dump($intent->getClientSecret());
    }

    public function get_setup_intent_payment_method()
    {
        $id = $this->getOpt('id');

        $intentManager = new Core\Payments\Stripe\Intents\Manager();
        $intent = $intentManager->get($id);
        var_dump($intent);
    }

    public function fix_connect()
    {
        $connectManager = new Core\Payments\Stripe\Connect\Manager();
        $i = 0;
        foreach ($connectManager->getList() as $account) {
            ++$i;
            echo "\n$i $account->id";
            var_dump($account->requirements->currently_due);
        }
    }

    public function remove_business_type()
    {
        $connectManager = new Core\Payments\Stripe\Connect\Manager();
        $account = $connectManager->getByAccountId($this->getOpt('id'));
        $connectManager->update($account);
    }

    public function create_stripe_lookups()
    {
        $connectManager = new Core\Payments\Stripe\Connect\Manager();
        $iterator = new Core\Analytics\Iterators\SignupsOffsetIterator();
        $iterator->token = $this->getOpt('token');
        $i = 0;
        $s = 0;
        foreach ($iterator as $user) {
            if (!$user instanceof Entities\User) {
                continue;
            }
            ++$i;
            var_dump($user->getMerchant());
            if ($stripeId = $user->getMerchant()['id']) {
                ++$s;
            }
            echo "\n$s/$i $user->guid {$stripeId} ($iterator->token)";
            if (!$stripeId) {
                continue;
            }
            try {
                $account = $connectManager->getByAccountId($stripeId);
                $account->setEmail($user->getEmail());
                $account->setUrl('https://www.minds.com/' . $user->username);
                $account->setMetadata([
                    'guid' => (string) $user->guid,
                ]);
                $connectManager->update($account);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Get payment intents and output to terminal. Must have at minimum customerId OR user ID but not both.
     * @param string $customerId - customer id to check.
     * @param string $userId - user ID to check.
     * @param string $endingBefore - payment id to get payments before, for pagination.
     * @param string $limit - limit of payments to get.
     * @return void
     * @example Usage:
     * - php cli.php Stripe getPaymentIntents --limit=1 --customerId=cus_123456789
     * - php cli.php Stripe getPaymentIntents --endingBefore=payment_123456 --userId=123456789
     */
    public function getPaymentIntents(): void
    {
        $customerId = $this->getOpt('customerId') ?? false;
        $userGuid = $this->getOpt('userGuid') ?? false;

        $opts = new GetPaymentsOpts();

        if (!$customerId xor $userGuid) {
            throw new UserErrorException('Must provider either customerId or userGuid, but not both');
        }

        if ($endingBefore = $this->getOpt('endingBefore') ?? false) {
            $opts->setEndingBefore($endingBefore);
        }

        if ($limit = $this->getOpt('limit') ?? false) {
            $opts->setLimit((int) $limit);
        }

        $paymentIntents = null;

        if ($userGuid) {
            $paymentIntents = $this->intentsManager->getPaymentIntentsByUserGuid($userGuid, $opts);
        }

        if ($customerId) {
            $opts->setCustomerId($customerId);
            $paymentIntents = $this->intentsManager->getPaymentIntents($opts);
        }

        foreach ($paymentIntents as $paymentIntent) {
            var_dump($paymentIntent);
        }
    }
}
