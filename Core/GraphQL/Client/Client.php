<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Client;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\GraphQL\Client\Enums\GraphQLRequestStatusEnum;
use Psr\Http\Message\ResponseInterface;

class Client
{
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {
    }

    /**
     * @param GraphQLQueryRequest $request
     * @return GraphQLResponse
     * @throws GuzzleException
     */
    public function runQuery(GraphQLQueryRequest $request): GraphQLResponse
    {
        $response = $this->httpClient->post(
            '',
            [
                'json' => [
                    'query' => $request->query,
                    'variables' => $request->variables,
                    'operationName' => $request->operationName,
                ],
            ]
        );

        return $this->buildResponse($response);
    }

    public function buildResponse(ResponseInterface $response): GraphQLResponse
    {
        $data = json_decode($response->getBody()->getContents(), true);

        return new GraphQLResponse(
            status: match ($response->getStatusCode()) {
                200 => GraphQLRequestStatusEnum::SUCCESS,
                400 => GraphQLRequestStatusEnum::BAD_REQUEST,
                default => GraphQLRequestStatusEnum::ERROR,
            },
            data: $data['data'] ?? [],
            errors: $data['errors'] ?? [],
        );
    }

    /**
     * @param GraphQLMutationRequest $request
     * @return GraphQLResponse
     * @throws GuzzleException
     */
    public function runMutation(GraphQLMutationRequest $request): GraphQLResponse
    {
        $response = $this->httpClient->post(
            '',
            [
                'json' => [
                    'query' => $request->mutation,
                    'variables' => $request->variables,
                    'operationName' => $request->operationName,
                ],
            ]
        );

        return $this->buildResponse($response);
    }
}
