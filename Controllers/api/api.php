<?php
/**
 * Minds API - pseudo router.
 *
 * @version 1
 *
 * @author Mark Harding
 *
 * @SWG\Swagger(
 *     schemes={"https"},
 *     host="www.minds.com",
 *     basePath="/api",
 *     @SWG\Info(
 *         version="1.0",
 *         title="Minds",
 *         description="",
 *         termsOfService="http://helloreverb.com/terms/",
 *         @SWG\Contact(
 *             email="apiteam@wordnik.com"
 *         ),
 *         @SWG\License(
 *             name="To be confirmed",
 *             url="http://www.minds.org/"
 *         )
 *     ),
 *     @SWG\ExternalDocumentation(
 *         description="Find out more about Minds",
 *         url="http://www.minds.org"
 *     )
 * )
 * @SWG\SecurityScheme(
 *   securityDefinition="minds_oauth2",
 *   type="oauth2",
 *   authorizationUrl="https://www.minds.com/oauth2/authorize",
 *   flow="implicit",
 *   scopes={
 *   }
 * )
 * @SWG\Info(title="Minds Public API", version="1.0")
 */

namespace Minds\Controllers\api;

use Minds\Interfaces;
use Minds\Api\Factory;

class api implements Interfaces\Api
{
    /** @var Request $request */
    private $request;

    /** @var Response $response */
    private $response;

    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    public function options($pages)
    {
        return Factory::build($pages, $this->request, $this->response);
    }

    public function get($pages)
    {
        return Factory::build($pages, $this->request, $this->response);
    }

    public function post($pages)
    {
        return Factory::build($pages, $this->request, $this->response);
    }

    public function put($pages)
    {
        return Factory::build($pages, $this->request, $this->response);
    }

    public function delete($pages)
    {
        return Factory::build($pages, $this->request, $this->response);
    }
}
