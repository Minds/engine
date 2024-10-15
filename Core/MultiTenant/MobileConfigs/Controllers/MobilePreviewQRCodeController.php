<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Controllers;

use Minds\Core\MultiTenant\MobileConfigs\Services\MobileAppPreviewQRCodeService;

/**
 * Mobile preview QR code controller.
 */
class MobilePreviewQRCodeController
{
    public function __construct(
        private readonly MobileAppPreviewQRCodeService $mobileAppPreviewQRCodeService,
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
}
