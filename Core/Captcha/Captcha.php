<?php
/**
 * Captcha Model
 */
namespace Minds\Core\Captcha;

use Minds\Traits\MagicAttributes;

class Captcha
{
    use MagicAttributes;

    /** @var string */
    private $jwtToken;

    /** @var string */
    private $clientText;

    /** @var string */
    private $base64Image;

    /**
     * Export the captcha
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'jwt_token' => $this->jwtToken,
            'base64_image' => $this->base64Image,
        ];
    }
}
