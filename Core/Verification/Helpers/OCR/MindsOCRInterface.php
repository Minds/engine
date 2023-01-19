<?php
declare(strict_types=1);

namespace Minds\Core\Verification\Helpers\OCR;

interface MindsOCRInterface
{
    public function processImageScan(string $image): string|false;
}
