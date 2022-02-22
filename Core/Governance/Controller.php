<?php

namespace Minds\Core\Governance;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Governance\ResponseBuilders\GetProposalsResponseBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Exceptions\ServerErrorException;
use Zend\Diactoros\ServerRequest;
use Minds\Exceptions\UserErrorException;

/**
 * The controller for the Social Compass module
 */
class Controller extends Exception
{

    public function __construct(
        private ?ManagerInterface $manager = null
    ) {
        $this->manager = $this->manager ?? new Manager();


        $activeSession = Di::_()->get("Sessions\ActiveSession");
        $this->loggedInUser = $activeSession->getUser();
    }

    /**
     * Returns the response containing the current set of proposals for the Governance Tab.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getProposals(ServerRequestInterface $request)
    {
        $result = $this->manager->retrieveProposals();

        try {
            $result = $this->manager->retrieveProposals();
            $responseBuilder = new GetProposalsResponseBuilder();
            return $responseBuilder->build($result);
        } catch (Exception $e) {
            throw new ServerErrorException($e);
        }
        
    }

    /**
     * Returns the response containing the current proposal found by the given id for the Governance Tab.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getProposalsById(ServerRequest $request): JsonResponse
    {
        $parameters = $request->getAttribute('parameters');
        if (!($parameters['id'] ?? null)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => ':id not provided'
            ]);
        }

        /** @var string */
        $id = $parameters['id'];

        $entity = $this->manager->retrieveProposal($id);

        if (!$entity) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'The proposal does not appear to exist',
            ]);
        }

        $responseBuilder = new GetProposalsResponseBuilder();
        return $responseBuilder->build($entity);
    }


    /**
     * Delete proposal enpoint
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function deleteProposal(ServerRequest $request): JsonResponse
    {
        $parameters = $request->getAttribute('parameters');
        if (!($parameters['id'] ?? null)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => ':id not provided'
            ]);
        }

        /** @var string */
        $id = $parameters['id'];


        if ($this->manager->delete($id)) {
            return new JsonResponse([
                'status' => 'success',
            ]);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'There was an unknown error deleting this proposal',
        ]);
    }

    /**
     * Insert proposal enpoint
     * @param ServerRequestInterface $request
     * @return ProposalModal
     * @throws UserErrorException
     */

    public function insertProposal(ServerRequestInterface $request)
    {
        $requestBody = json_decode($request->getBody()->getContents());

        if (empty($requestBody)) {
            throw new UserErrorException("Missing proposal properties provided");
        }

        $entity = $this->manager->insert($requestBody);

        return new JsonResponse([
            'data' => $entity,
            'status' => 'success'
        ]);
    }
}
