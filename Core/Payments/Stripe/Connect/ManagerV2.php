<?php
namespace Minds\Core\Payments\Stripe\Connect;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\StopEventException;
use Minds\Exceptions\UserErrorException;
use Stripe;
use Stripe\Account;
use Stripe\Exception\ApiErrorException;

class ManagerV2
{
    public function __construct(
        private ?Config $config = null,
        private ?StripeClient $stripeClient = null,
        private ?Save $save = null,
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->stripeClient ??= new StripeClient();
        $this->save ??= new Save();
    }

    /**
     * Creates a stripe connect account
     * @param User $user
     * @return Account
     * @throws ApiErrorException
     * @throws UserErrorException
     * @throws UnverifiedEmailException
     * @throws StopEventException
     */
    public function createAccount(User $user): Stripe\Account
    {
        try {
            $this->getAccountIdByUser($user);
            throw new UserErrorException("A stripe account already exists for this user", 400);
        } catch (NotFoundException $e) {
            // All good, we want a NotFoundException
        }

        $account = $this->stripeClient->accounts->create([
            'type' => 'express',
            'email' => $user->getEmail(),
            'metadata' => [
                'user_guid' => (string) $user->getGuid(),
            ],
            'business_profile' => [
                //'url' => $this->getSiteUrl() . $user->getUsername(),
                'url' => 'https://www.minds.com/minds',
            ],
            'settings' => [
                'payouts' => [
                    'schedule' => [
                        'interval' => 'monthly',
                        'monthly_anchor' => 28,
                        'delay_days' => 30,
                    ]
                ]
            ]
        ]);

        /**
         * Update the user with the stripe id
         */
        $user->setMerchant([
                'service' => 'stripe',
                'id' => $account->id,
        ]);
        $this->save->setEntity($user)->save();

        return $account;
    }

    /**
     * Returns a stripe user account, if exists
     * @param User $user
     * @return Account
     * @throws ApiErrorException
     * @throws NotFoundException
     */
    public function getAccount(User $user): Stripe\Account
    {
        return $this->stripeClient->accounts->retrieve($this->getAccountIdByUser($user));
    }

    /**
     * Redirect to onboarding
     * @param User $user
     * @return string
     * @throws NotFoundException
     * @throws ApiErrorException
     */
    public function getAccountLink(User $user): string
    {
        $accountLink = $this->stripeClient->accountLinks->create([
            'account' => $this->getAccountIdByUser($user),
            'refresh_url' => $this->getSiteUrl(). 'api/v3/payments/stripe/connect/onboarding',
            'return_url' => $this->getSiteUrl() . 'wallet/cash/settings',
            'type' => 'account_onboarding',
            'collect' => 'currently_due',
        ]);

        return $accountLink->url;
    }

    /**
     * @param User $user
     * @return string
     * @throws NotFoundException
     */
    protected function getAccountIdByUser(User $user): string
    {
        $merchantData = $user->getMerchant() ?: [];
        if (isset($merchantData['service']) && $merchantData['service'] === 'stripe') {
            return $merchantData['id'];
        }

        throw new NotFoundException("User does not have Stripe Connect Account");
    }

    /**
     * Helper to get the site url
     * @return string
     */
    protected function getSiteUrl(): string
    {
        return $this->config->get('site_url');
    }
}
