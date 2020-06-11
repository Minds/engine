<?php
namespace Minds\Core\Captcha;

use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Di\Di;

class Manager
{
    /** @var ImageGenerator */
    private $imageGenerator;

    /** @var JWT */
    private $jwt;

    /** @var string */
    private $secret;

    /** @var Config */
    private $config;

    public function __construct($imageGenerator = null, $jwt = null, $config = null)
    {
        $this->imageGenerator = $imageGenerator ?? new ImageGenerator;
        $this->jwt = $jwt ?? new Jwt();
        $this->config = $config ?? Di::_()->get('Config');
        $this->secret = $this->config->get('captcha') ? $this->config->get('captcha')['jwt_secret'] : 'todo';
        $this->jwt->setKey($this->secret);
    }

    /**
     * Verify from client json
     * @param string $json
     * @return bool
     */
    public function verifyFromClientJson(string $json): bool
    {
        $data = json_decode($json, true);
        $captcha = new Captcha();
        $captcha->setJwtToken($data['jwtToken'])
            ->setClientText($data['clientText']);
        return $this->verify($captcha);
    }

    /**
     * Verify if a captcha is valid
     * @param Captcha $captcha
     * @return bool
     */
    public function verify(Captcha $captcha): bool
    {
        if (isset($_COOKIE['captcha_bypass'])) {
            return $this->verifyBypass($captcha);
        }

        $jwtToken = $captcha->getJwtToken();
        $decodedJwtToken = $this->jwt->decode($jwtToken);
        $salt = $decodedJwtToken['salt'];
        $hash = $decodedJwtToken['public_hash'] ;

        // This is what the client has said the captcha image has
        $clientText = $captcha->getClientText();

        // Now convert this back to our hash
        $clientHash = $this->buildCaptchaHash($clientText, $salt);

        return $clientHash === $hash;
    }

    /**
     * This is used for testing purposes and for e2e to bypass
     * the captvha
     * @param Captcha $captcha
     * @return bool
     */
    protected function verifyBypass(Captcha $captcha): bool
    {
        if (!isset($_COOKIE['captcha_bypass'])) {
            return false;
        }

        $bypassKey = $this->config->get('captcha')['bypass_key'];

        $decoded = $this->jwt
            ->setKey($bypassKey)
            ->decode($_COOKIE['captcha_bypass']);

        $inputted = (int) $decoded['data'];

        return $inputted == $captcha->getClientText();
    }

    /**
     * Output the captcha
     * @param string $forcedText
     * @return void
     */
    public function build(string $forcedText = ''): Captcha
    {
        $text = $forcedText ?: $this->getRandomText(6);

        $now = time();
        $expires = $now + 300; // Captcha are good for 5 minutes

        $salt = $this->jwt->randomString();
        $jwtToken = $this->jwt
            ->setKey($this->secret)
            ->encode([
                'public_hash' => $this->buildCaptchaHash($text, $salt),
                'salt' => $salt,
            ], $expires, $now);

        $image = $this->imageGenerator
            ->setText($text)
            ->build();

        $captcha = new Captcha();
        $captcha->setBase64Image($image)
            ->setJwtToken($jwtToken);

        return $captcha;
    }

    /**
     * Get the random text
     * @param int $length
     * @return sdtring
     */
    protected function getRandomText(int $length): string
    {
        $chars = array_merge(
            range(1, 9), // We don't want 0's
            range('A', 'Z'),
            range('a', 'z')
        );
        // We don't want O's, o's, I's or l's
        $chars = array_diff($chars, ["O", "o", "I", "l"]);
        shuffle($chars);

        $text="";
        for ($i = 0; $i < $length; $i++) {
            $text .= $chars[array_rand($chars)];
        }

        return $text;
    }

    /**
     * Return hash based on text and salt with a secret
     * @param string $text
     * @param string $salt
     * @return string
     */
    protected function buildCaptchaHash(string $text, string $salt): string
    {
        return hash('sha1', $text . $this->secret . $salt);
    }
}
