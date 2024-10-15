<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Services;

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

/**
 * Mobile preview QR code service.
 */
class MobileAppPreviewQRCodeService
{
    public function __construct(
        private readonly MobileConfigRepository $mobileConfigRepository,
        private readonly MultiTenantBootService $multiTenantBootService,
        private readonly Config                 $config
    ) {
    }

    /**
     * Gets the QR code blob for the mobile app preview.
     * @param int|null $tenantId - The tenant ID.
     * @return string - The QR code.
     */
    public function getBlob(int $tenantId = null): string
    {
        if (!$tenantId) {
            $tenantId = $this->config->get('tenant_id');
        }

        $this->multiTenantBootService->bootFromTenantId($tenantId);

        if (!$url = $this->getVersionlessPreviewQRCode($tenantId)) {
            return '';
        }

        return (new QRCode(
            new QROptions([
                'outputType' => QROutputInterface::IMAGICK,
                'imagickFormat' => 'png',
                'quality' => 90,
                'scale' => 20,
                'outputBase64' => false,
            ])
        ))->render($url);
    }

    /**
     * Gets the QR code for the mobile app preview.
     * @param int|null $tenantId - The tenant ID.
     * @return string|null - The QR code.
     */
    public function getVersionlessPreviewQRCode(int $tenantId = null): ?string
    {
        if (!$tenantId) {
            $tenantId = $this->config->get('tenant_id');
        }

        $mobileConfig = $this->mobileConfigRepository->getMobileConfig($tenantId);
        return $mobileConfig?->getVersionlessPreviewQRCode() ?? null;
    }
}
