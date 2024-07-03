<?php
namespace Minds\Core\Blockchain\UnstoppableDomains;

use Minds\Entities\User;
use Exception;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\UniqueOnChainAddress;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * UnstoppableDomains Controller
 */
class Controller
{
    /**
     * Controller constructor.
     */
    public function __construct(protected ?Client $client = null)
    {
        $this->client ??= new Client();
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getDomains(ServerRequest $request): JsonResponse
    {
        /** @var string */
        $walletAddress = $request->getAttribute('parameters')['walletAddress'];

        return new JsonResponse([
            'status' => 'success',
            'domains' => [],
        ]);
    }
}
