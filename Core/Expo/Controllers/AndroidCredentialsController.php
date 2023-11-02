<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Controllers;

use Minds\Core\Expo\Services\AndroidCredentialsService;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Expo Android Controller
 */
class AndroidCredentialsController
{
    public function __construct(
        private AndroidCredentialsService $androidCredentialsService,
    ) {
    }

    /**
     * Set project credentials in expo for an Android app.
     * @param ServerRequest $request - The request.
     * @return JsonResponse - The response.
     */
    public function setProjectCredentials(ServerRequest $request): JsonResponse
    {
        $requestBody = $request->getParsedBody();
        $applicationIdentifier = $requestBody['application_identifier'] ?? throw new UserErrorException('Missing application_identifier');
        $androidKeystorePassword = $requestBody['android_keystore_password'] ?? throw new UserErrorException('Missing android_keystore_password');
        $androidKeystoreKeyAlias = $requestBody['android_keystore_key_alias'] ?? throw new UserErrorException('Missing android_keystore_key_alias');
        $androidKeystoreKeyPassword = $requestBody['android_keystore_key_password'] ?? throw new UserErrorException('Missing android_keystore_key_password');
        $androidBase64EncodedKeystore = $requestBody['android_base64_keystore'] ?? throw new UserErrorException('Missing android_base64_keystore');
        $googleServiceAccountJson = $requestBody['google_service_account_json'] ?? throw new UserErrorException('Missing google_service_account_json');
        $googleCloudMessagingToken = $requestBody['google_cloud_messaging_token'] ?? throw new UserErrorException('Missing google_cloud_messaging_token');

        $response = $this->androidCredentialsService->setupProjectCredentials(
            applicationIdentifier: $applicationIdentifier,
            androidKeystorePassword: $androidKeystorePassword,
            androidKeystoreKeyAlias: $androidKeystoreKeyAlias,
            androidKeystoreKeyPassword: $androidKeystoreKeyPassword,
            androidBase64EncodedKeystore: $androidBase64EncodedKeystore,
            googleServiceAccountJson: $googleServiceAccountJson,
            googleCloudMessagingToken: $googleCloudMessagingToken
        );
        
        return new JsonResponse($response);
    }

    // /**
    //  * Delete project credentials in expo for an Android app.
    //  * @param ServerRequest $request - The request.
    //  * @return JsonResponse - The response.
    //  */
    // public function deleteProjectCredentials(ServerRequest $request): JsonResponse
    // {
    //     // TODO: This endpoint needs to be secured to release if we need it - we may want to pull the
    //     // androidAppCredentials from tenant configs rather than having it as an input parameter.
    //     $parameters = $request->getAttribute('parameters');

    //     $androidAppCredentialsId = $parameters['appCredentialsId'] ?? throw new UserErrorException('Missing appCredentialsId');

    //     $this->androidCredentialsService->deleteProjectCredentials(
    //         androidAppCredentialsId: $androidAppCredentialsId,
    //     );

    //     return new JsonResponse([]);
    // }
}
