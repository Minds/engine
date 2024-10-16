<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Controllers;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileAppPreviewQRCodeService;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Mobile preview QR code controller.
 */
class MobilePreviewQRCodeController
{
    public function __construct(
        private readonly MobileAppPreviewQRCodeService $mobileAppPreviewQRCodeService,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Gets the QR code for the mobile app preview.
     * @return void
     */
    public function getQrCode(): void
    {
        $contents = $this->mobileAppPreviewQRCodeService->getBlob();

        if (empty($contents)) {
            exit;
        }

        header('Content-type: image/png');
        header('Expires: ' . date('r', time() + 864000));
        header("Pragma: public");
        header("Cache-Control: public");

        echo $contents;
        exit;
    }

    /**
     * Redirects to the mobile preview deep link URL.
     * @param ServerRequestInterface $request
     * @return RedirectResponse
     */
    public function redirectToMobilePreviewDeepLink(ServerRequestInterface $request): RedirectResponse
    {
        $deepLinkUrl = $this->mobileAppPreviewQRCodeService->getVersionlessPreviewQRCode();
        
        if (!$deepLinkUrl) {
            // We have no mechanism to handle this in the event that something unusual has gone wrong,
            // So we'll redirect the network site for now.
            $this->logger->error('Mobile preview deep link URL not set');
            return new RedirectResponse('https://networks.minds.com/your-own-app');
        }
        
        return new RedirectResponse($deepLinkUrl);
    }
}
