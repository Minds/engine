<?php

namespace Minds\Core\Governance\ResponseBuilders;

use Minds\Api\Exportable;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Exceptions\ServerErrorException;

/**
 * The response builder for the GET api/v3/governance/proposals endpoint
 */
class GetProposalsResponseBuilder
{
    /**
     * Build the response object for the Governance getProposals endpoint
     * @param array $proposals The list of Governance proposals
     *
     *              [
     *                  "proposals": ProposalModel[],
     *                  "proposalsProvided": bool,
     *                  "status": bool
     *              ]
     *
     * @return JsonResponse
     */
    public function build(array $proposals): JsonResponse
    {
        $response = [
            "status" => "success"
        ];
        $response = array_merge($response, $proposals);
        return new JsonResponse($response);
    }
}
