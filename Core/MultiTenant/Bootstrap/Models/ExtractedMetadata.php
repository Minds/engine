<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Models;

use Minds\Traits\MagicAttributes;

/**
 * Metadata extracted from the bootstrap process.
 * @method string getLogoUrl()
 * @method string getLogoData()
 * @method string getDescription()
 * @method string getPublisher()
 * @method void setLogoUrl(string $logoUrl)
 * @method void setLogoData(string $logoData)
 * @method void setDescription(string $description)
 * @method void setPublisher(string $publisher)
 */
class ExtractedMetadata
{
    use MagicAttributes;

    /** @var string|null URL of the logo. */
    private ?string $logoUrl;

    /** @var string|null Data of the logo. */
    private ?string $logoData;

    /** @var string|null Description of the tenant. */
    private ?string $description;

    /** @var string|null Publisher of the tenant. */
    private ?string $publisher;

    public function __construct(
        ?string $logoUrl = null,
        ?string $logoData = null,
        ?string $description = null,
        ?string $publisher = null
    ) {
        $this->logoUrl = $logoUrl;
        $this->logoData = $logoData;
        $this->description = $description;
        $this->publisher = $publisher;
    }
}
