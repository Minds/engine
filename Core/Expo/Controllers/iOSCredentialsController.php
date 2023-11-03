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
        $bundleIdentifier = $requestBody['bundle_identifier'] ??
            throw new UserErrorException('Missing bundle_identifier');
        $distributionCertP12 = $requestBody['distribution_cert_p12'] ??
            throw new UserErrorException('Missing distribution_cert_p12');
        $distributionCertPassword = $requestBody['distribution_cert_password'] ??
            throw new UserErrorException('Missing distribution_cert_password');
        $appleProvisioningProfile = $requestBody['provisioning_profile'] ??
            throw new UserErrorException('Missing provisioning_profile');

        $iosDistributionType = $requestBody['ios_distribution_type'] ?? 'DEVELOPMENT';
        $pushKeyP8 = $requestBody['push_key_p8'] ?? null;
        $pushKeyIdentifier = $requestBody['push_key_identifier'] ?? null;
        $ascKeyP8 = $requestBody['asc_key_p8'] ?? null;
        $ascKeyIdentifier = $requestBody['asc_key_identifier'] ?? null;
        $ascKeyIssuerIdentifier = $requestBody['asc_issuer_identifier'] ?? null;
        $ascName = $requestBody['asc_name'] ?? null;

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

    /**
     * Update project credentials in Expo for an iOS app.
     * @param ServerRequest $request - The request.
     * @return JsonResponse - The response.
     */
    public function updateProjectCredentials(ServerRequest $request): JsonResponse
    {
        $requestBody = $request->getParsedBody();

        $pushKeyP8 = $requestBody['push_key_p8'] ?? null;
        $pushKeyIdentifier = $requestBody['push_key_identifier'] ?? null;
        $ascKeyP8 = $requestBody['asc_key_p8'] ?? null;
        $ascKeyIdentifier = $requestBody['asc_key_identifier'] ?? null;
        $ascKeyIssuerIdentifier = $requestBody['asc_issuer_identifier'] ?? null;
        $ascName = $requestBody['asc_name'] ?? null;

        $response = $this->iosCredentialsService->updateProjectCredentials(
            pushKeyP8: $pushKeyP8,
            pushKeyIdentifier: $pushKeyIdentifier,
            ascKeyP8: $ascKeyP8,
            ascKeyIdentifier: $ascKeyIdentifier,
            ascKeyIssuerIdentifier: $ascKeyIssuerIdentifier,
            ascName: $ascName
        );

        return new JsonResponse($response);
    }

    /**
     * Delete project credentials in expo for an iOS app.
     * @param ServerRequest $request - The request.
     * @return JsonResponse - The response.
     */
    public function deleteProjectCredentials(ServerRequest $request): JsonResponse
    {
        $this->iosCredentialsService->deleteProjectCredentials();
        return new JsonResponse([]);
    }
}
