<?php
namespace Minds\Core\Payments\Stripe\Connect;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(private ?ManagerV2 $manager = null)
    {
        $this->manager ??= Di::_()->get('Stripe\Connect\ManagerV2');
    }

    /**
     * Creates a stripe connect (express) account
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function createAccount(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');
        
        $account = $this->manager->createAccount($user);

        return new JsonResponse([
            'id' => $account->id,
        ]);
    }


    /**
     * Returns information about the users stripe connect status
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getAccount(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $account = $this->manager->getAccount($user);

        /**
         * Below are the keys we wish to expose to our users about their Stripe Account
         */
        $exportKeys = [
            'id',
            'payouts_enabled',
            'charges_enabled',
            'requirements',
        ];

        /**
         * Rebuild the export based on the keys above
         */
        $exportedAccount = [];
        foreach ($exportKeys as $key) {
            $exportedAccount[$key] = $account->$key;
        }

        return new JsonResponse($exportedAccount);
    }

    /**
     * Takes a user to the onboarding page on stripe
     * @param ServerRequest $request
     * @return RedirectResponse
     */
    public function redirectToOnboarding(ServerRequest $request): RedirectResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');
    
        $link = $this->manager->getAccountLink($user);
    
        return new RedirectResponse($link);
    }
}
