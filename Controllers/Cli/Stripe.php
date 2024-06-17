<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Interfaces;
use Minds\Entities;
use Minds\Core\Payments\Models\GetPaymentsOpts;
use Minds\Core\Supermind\Payments\SupermindPaymentProcessor;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Payments\Stripe\Keys\StripeKeysService;
use Minds\Core\Payments\Stripe\Webhooks\Services\SubscriptionsWebhookService;
use Minds\Core\Wire\Manager as WireManager;
use Minds\Core\Wire\Wire;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

class Stripe extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?IntentsManagerV2 $intentsManager = null,
        private ?SupermindPaymentProcessor $supermindPaymentProcessor = null,
        private ?WireManager $wireManager = null,
        private ?MultiTenantBootService $multiTenantBootService = null,
        private ?StripeKeysService $stripeKeysService = null,
        private ?SubscriptionsWebhookService $subscriptionsWebhookService = null
    ) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->intentsManager ??= new IntentsManagerV2();
        $this->supermindPaymentProcessor ??= new SupermindPaymentProcessor();
        $this->wireManager ??= Di::_()->get('Wire\Manager');
        $this->multiTenantBootService ??= Di::_()->get(MultiTenantBootService::class);
        $this->stripeKeysService ??= Di::_()->get(StripeKeysService::class);
        $this->subscriptionsWebhookService ??= Di::_()->get(SubscriptionsWebhookService::class);
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
     * @param string $startingAfter - payment id to get payments after, for pagination.
     * @param string $limit - limit of payments to get.
     * @return void
     * @example Usage:
     * - php cli.php Stripe getPaymentIntents --limit=1 --customerId=cus_123456789
     * - php cli.php Stripe getPaymentIntents --startingAfter=payment_123456 --userId=123456789
     */
    public function getPaymentIntents(): void
    {
        $customerId = $this->getOpt('customerId') ?? false;
        $userGuid = $this->getOpt('userGuid') ?? false;

        $opts = new GetPaymentsOpts();

        if (!$customerId xor $userGuid) {
            throw new UserErrorException('Must provider either customerId or userGuid, but not both');
        }

        if ($startingAfter = $this->getOpt('startingAfter') ?? false) {
            $opts->setStartingAfter($startingAfter);
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

    /**
     * Updates descriptions of payment intents.
     * @param string $dryRun - whether to dry run without changes being made.
     * @param string $verbose - extra logging information.
     * @param string $pageSize - size of pages to load at a time.
     * @param string $overwrite - whether existing descriptions should be overwritten.
     * @return void
     * @example Usage:
     * - php cli.php Stripe updatePaymentDescriptions --pageSize=10 --overwrite --verbose --dryRun
     */
    public function updatePaymentDescriptions(): void
    {
        $dryRun = $this->getOpt('dryRun') ?? false;
        $verbose = $this->getOpt('verbose') ?? false;
        $pageSize = $this->getOpt('pageSize') ?? 10;
        $overwrite = $this->getOpt('overwrite') ?? false;

        $opts = (new GetPaymentsOpts())->setLimit($pageSize);

        $count = 0;
        foreach ($this->intentsManager->getPaymentIntentsGenerator($opts) as $intent) {
            $count++;
            try {
                $metadata = $intent['metadata']->toArray();
                
                // if it has a description already, we don't need to set one unless we are overwriting.
                if (!$overwrite && $intent['description']) {
                    if ($verbose) {
                        $this->out("{$intent['id']} has a description already");
                    }
                    continue;
                }

                $description = 'Minds Payment';

                // get new description string.
                if (isset($metadata['boost_guid'])) {
                    // if it's a boost, try to construct it.
                    $boostSender = $this->entitiesBuilder->single($metadata['boost_sender_guid']); //TODO: WRONG GUID
                    if (!$boostSender || !$boostSender instanceof User) {
                        $description = "Boost from {$metadata['boost_sender_guid']}";
                    } else {
                        $description = "Boost from unknown";
                    }
                } elseif (isset($metadata['receiver_guid'])) {
                    $receiver = $this->entitiesBuilder->single($metadata['receiver_guid']);

                    if (!$receiver instanceof User) {
                        // if receiver isn't a user, we can't derive the target.
                        // this can happen when sharing between localhost and sandbox
                        // or if user has deleted themselves.
                        $description = "Minds Payment ({$metadata['receiver_guid']})";
                    } elseif (isset($metadata['supermind'])) {
                        // supermind takes precedence over wire.
                        $description = $this->supermindPaymentProcessor->getDescription($receiver);
                    } else {
                        // fallback to it being a wire if nothing else fits.
                        $description = $this->wireManager->getDescriptionFromWire(
                            (new Wire())
                                ->setMethod('usd')
                                ->setReceiver($receiver)
                                ->setAmount($intent['amount'])
                        );
                    }
                }

                $this->out("Change description for {$intent['id']} to: '$description'");

                if ($description && !$dryRun) {
                    $this->intentsManager->updatePaymentIntentById($intent['id'], [
                        'description' => $description
                    ]);
                }
            } catch (\Exception $e) {
                $this->out($e->getMessage());
            }
        }

        $this->out("Completed. Processed $count PaymentIntents");
    }

    /**
     * Sync site membership subscription webhooks for all tenants with Stripe kets set.
     * Example usage: 
     * ```
     * php cli.php Stripe sync_membership_webhooks
     * ``` 
     * @return void
     */
    public function sync_membership_webhooks(): void
    {
        $this->out("Syncing membership webhooks...");
        $keyPairs = $this->stripeKeysService->getAllKeys();

        foreach ($keyPairs as $keyPair) {
            $this->out("Syncing webhook for tenant: {$keyPair['tenant_id']}...");

            try {
                $this->multiTenantBootService->bootFromTenantId((int) $keyPair['tenant_id']);

                $this->out(
                    $this->subscriptionsWebhookService->createSubscriptionsWebhook() ?
                        "Webhook created, or already exists for tenant: {$keyPair['tenant_id']}." :
                        "Webhook not created for tenant: {$keyPair['tenant_id']}"
                );
            } catch(\Exception $e) {
                $this->out($e->getMessage());
            }
        }

        $this->out('Done.');
    }
}
