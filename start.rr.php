<?php
/**
 * This is will boot an async server, powered by https://reactphp.org/
 */
require_once(dirname(__FILE__) . "/start.php");

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Minds\Core\Data\cache\InMemoryCache;
use Minds\Core\Di\Di;
use Minds\Entities\Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;
use TheCodingMachine\GraphQLite\Context\Context;

error_reporting(E_ALL);

$router = new Minds\Core\Router();


$worker = Worker::create();

$factory = new Psr17Factory();

$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

// Prewarm Graphql

try {
    /** @var Schema */
    $schema = Di::_()->get(Schema::class);
    $result = GraphQL::executeQuery($schema, "\n    query IntrospectionQuery {\n      __schema {\n        \n        queryType { name }\n        mutationType { name }\n        subscriptionType { name }\n        types {\n          ...FullType\n        }\n        directives {\n          name\n          description\n          \n          locations\n          args {\n            ...InputValue\n          }\n        }\n      }\n    }\n\n    fragment FullType on __Type {\n      kind\n      name\n      description\n      \n      fields(includeDeprecated: true) {\n        name\n        description\n        args {\n          ...InputValue\n        }\n        type {\n          ...TypeRef\n        }\n        isDeprecated\n        deprecationReason\n      }\n      inputFields {\n        ...InputValue\n      }\n      interfaces {\n        ...TypeRef\n      }\n      enumValues(includeDeprecated: true) {\n        name\n        description\n        isDeprecated\n        deprecationReason\n      }\n      possibleTypes {\n        ...TypeRef\n      }\n    }\n\n    fragment InputValue on __InputValue {\n      name\n      description\n      type { ...TypeRef }\n      defaultValue\n      \n      \n    }\n\n    fragment TypeRef on __Type {\n      kind\n      name\n      ofType {\n        kind\n        name\n        ofType {\n          kind\n          name\n          ofType {\n            kind\n            name\n            ofType {\n              kind\n              name\n              ofType {\n                kind\n                name\n                ofType {\n                  kind\n                  name\n                  ofType {\n                    kind\n                    name\n                  }\n                }\n              }\n            }\n          }\n        }\n      }\n    }\n", null, new Context(), []);
} catch (\Exception $e) {
    var_dump($e);
}

while (true) {

    try {
        $request = $psr7->waitRequest();
        if ($request === null) {
            break;
        }
    } catch (\Throwable $e) {
        $psr7->respond(new Response(400));
        continue;
    }

    try {
        $response = $router->handleRequest($request);
        $psr7->respond($response);
    } catch (\Throwable $e) {
        $psr7->respond(new Response(500, [], 'Something Went Wrong!'));
        $psr7->getWorker()->error((string)$e);
    } finally {
        // Clear the per-request caches
        $cache = Di::_()->get(InMemoryCache::class);
        $cache->clear();

        // Tmp (needs refactoring at a lower level)
        global $USERNAME_TO_GUID_MAP_CACHE;
        $USERNAME_TO_GUID_MAP_CACHE = [];
    }
}
