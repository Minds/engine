<?php
namespace Minds\Core\GraphQL;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Minds\Core\Di\Di;
use Minds\Core\GraphQL\Services\AuthorizationService;
use Minds\Core\GraphQL\Services\AuthService;
use Minds\Core\Security\Rbac\Services\RolesService;
use Psr\Http\Message\ServerRequestInterface;
use TheCodingMachine\GraphQLite\Context\Context;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function exec(ServerRequestInterface $request): JsonResponse
    {
        $rawInput = $request->getBody()->getContents();

        $input = json_decode($rawInput, true);
        $query = $input['query'];
        $variableValues = isset($input['variables']) ? $input['variables'] : null;
  
        $schema = Di::_()->get(Schema::class);

        $result = GraphQL::executeQuery($schema, $query, null, new Context(), $variableValues);
        $output = $result->toArray();

        if ($result->errors) {
            foreach ($result->errors as $error) {
                error_log($error);
            }
        }

        return new JsonResponse($output);
    }
}
