<?php
namespace Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Entities\User;
use Exception;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\UniqueOnChainAddress;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * UniqueOnChain Controller
 * @package Minds\Core\Blockchain\LiquidityPostions
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct($manager = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Returns if validated onchain address or not
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function get(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $isUnique = $this->manager->isUnique($user);

        return new JsonResponse([
            'status' => 'success',
            'unique' => $isUnique,
            'address' => $user->getEthWallet(),
        ]);
    }

    /**
     * Returns all unique addresses
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getAll(ServerRequest $request): JsonResponse
    {
        $list = iterator_to_array($this->manager->getAll());

        return new JsonResponse([
            'status' => 'success',
            'addresses' => array_map(function ($uniqueOnChain) {
                return $uniqueOnChain->export();
            }, $list),
        ]);
    }

    /**
     * Confirms validation
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function validate(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $body = $request->getParsedBody();

        $address = $body['address'];
        $payload = $body['payload'];
        $signature = $body['signature'];

        $uniqueAddress = new UniqueOnChainAddress();
        $uniqueAddress->setAddress($address)
            ->setUserGuid((string) $user->getGuid())
            ->setPayload($payload)
            ->setSignature($signature);

        $success = $this->manager->add($uniqueAddress, $user, true);

        if (!$success) {
            throw new UserErrorException("Could not verify your signature");
        }
         
        return new JsonResponse([
            'status' => 'success',
        ]);
    }

    /**
     * Remove validation
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function unValidate(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $address = $user->getEthWallet();

        $uniqueAddress = new UniqueOnChainAddress();
        $uniqueAddress->setAddress($address)
            ->setUserGuid((string) $user->getGuid());

        $success = $this->manager->delete($uniqueAddress, $user, true);

        if (!$success) {
            throw new UserErrorException("Unkown error");
        }
         
        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
