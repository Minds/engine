<?php
namespace Minds\Core\GraphQL;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use Minds\Core\Di\Di;
use Minds\Core\GraphQL\Services\AuthorizationService;
use Minds\Core\GraphQL\Services\AuthService;
use Minds\Core\Router\Exceptions\ForbiddenException;
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

        // Excessive query sizes are not accepted
        if (strlen($query) > 5000) {
            throw new ForbiddenException();
        }
  
        $schema = Di::_()->get(Schema::class);

        $result = GraphQL::executeQuery(
            schema: $schema,
            source: $query,
            contextValue: new Context(),
            variableValues: $variableValues,
            validationRules: [
                new \GraphQL\Validator\Rules\QueryDepth(15),
            ]
        );
        $output = $result->toArray();

        if ($result->errors) {
            foreach ($result->errors as $error) {
                error_log($error);
            }
        }

        return new JsonResponse($output);
    }
}
