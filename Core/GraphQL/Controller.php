<?php
namespace Minds\Core\GraphQL;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Minds\Core\Di\Di;
use Minds\Core\GraphQL\Services\AuthorizationService;
use Minds\Core\GraphQL\Services\AuthService;
use TheCodingMachine\GraphQLite\Context\Context;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function exec(ServerRequest $request): JsonResponse
    {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        $query = $input['query'];
        $variableValues = isset($input['variables']) ? $input['variables'] : null;

        $authService = new AuthService($request->getAttribute('_user'));
        $authorizationService = new AuthorizationService();
        $schema = Di::_()->get(Schema::class, ['auth_service' => $authService, 'authorization_service' => $authorizationService]);

        $result = GraphQL::executeQuery($schema, $query, null, new Context(), $variableValues);
        $output = $result->toArray();

        if ($result->errors) {
            foreach ($result->errors as $error) {
                error_log($error->getMessage());
            }
        }

        return new JsonResponse($output);
    }
}
