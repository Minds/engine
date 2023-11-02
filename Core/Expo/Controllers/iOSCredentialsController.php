<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Controllers;

use Minds\Core\Expo\Services\iOSCredentialsService;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Expo iOS Controller.
 */
class iOSCredentialsController
{
    public function __construct(
        private iOSCredentialsService $iosCredentialsService,
    ) {
    }

    /**
     * Set project credentials in expo for an iOS app.
     * @param ServerRequest $request - The request.
     * @return JsonResponse - The response.
     */
    public function setProjectCredentials(ServerRequest $request): JsonResponse
    {
        $requestBody = $request->getParsedBody();
        $bundleIdentifier = $requestBody['bundle_identifier'] ?? throw new UserErrorException('Missing bundle_identifier');
        $iosDistributionType = $requestBody['ios_distribution_type'] ?? 'DEVELOPMENT';
        $distributionCertP12 = $requestBody['distribution_cert_p12'] ?? throw new UserErrorException('Missing distribution_cert_p12');
        $distributionCertPassword = $requestBody['distribution_cert_password'] ?? throw new UserErrorException('Missing distribution_cert_password');
        $appleProvisioningProfile = $requestBody['provisioning_profile'] ?? throw new UserErrorException('Missing provisioning_profile');
        $pushKeyP8 = $requestBody['push_key_p8'] ?? throw new UserErrorException('Missing push_key_p8');
        $pushKeyIdentifier = $requestBody['push_key_identifier'] ?? throw new UserErrorException('Missing push_key_identifier');
        $ascKeyP8 = $requestBody['asc_key_p8'] ?? throw new UserErrorException('Missing asc_key_p8');
        $ascKeyIdentifier = $requestBody['asc_key_identifier'] ?? throw new UserErrorException('Missing asc_key_identifier');
        $ascKeyIssuerIdentifier = $requestBody['asc_issuer_identifier'] ?? throw new UserErrorException('Missing asc_issuer_identifier');
        $ascName = $requestBody['asc_name'] ?? throw new UserErrorException('Missing asc_name');

        $result = $this->iosCredentialsService->setupProjectCredentials(
            bundleIdentifier: $bundleIdentifier,
            iosDistributionType: $iosDistributionType,
            distributionCertP12: $distributionCertP12,
            distributionCertPassword: $distributionCertPassword,
            appleProvisioningProfile: $appleProvisioningProfile,
            pushKeyP8: $pushKeyP8,
            pushKeyIdentifier: $pushKeyIdentifier,
            ascKeyP8: $ascKeyP8,
            ascKeyIdentifier: $ascKeyIdentifier,
            ascKeyIssuerIdentifier: $ascKeyIssuerIdentifier,
            ascName: $ascName,
        );
        
        return new JsonResponse($result);
    }

    // /**
    //  * Delete project credentials in expo for an iOS app.
    //  * @param ServerRequest $request - The request.
    //  * @return JsonResponse - The response.
    //  */
    // public function deleteProjectCredentials(ServerRequest $request): JsonResponse
    // {
    //     // TODO: This endpoint needs to be secured to release if we need it - we may want to pull the
    //     // iosAppCredentials from tenant configs rather than having it as an input parameter.
    //     $parameters = $request->getAttribute('parameters');

    //     $iosAppCredentialsId = $parameters['appCredentialsId'] ?? throw new UserErrorException('Missing appCredentialsId');

    //     $this->iosCredentialsService->deleteProjectCredentials(
    //         iosAppCredentialsId: $iosAppCredentialsId,
    //     );

    //     return new JsonResponse([]);
    // }
}
