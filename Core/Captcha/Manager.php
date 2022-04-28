<?php
namespace Minds\Core\Captcha;

use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Log;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Core\Security\RateLimits\RateLimitExceededException;

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

    /** @var Log\Logger */
    private $logger;

    /** @var KeyValueLimiter */
    private $keyValueLimiter;

    public function __construct(
        $imageGenerator = null,
        $jwt = null,
        $config = null,
        $logger = null,
        $keyValueLimiter = null,
        private ?BypassManager $bypassManager = null,
    ) {
        $this->imageGenerator = $imageGenerator ?? new ImageGenerator;
        $this->jwt = $jwt ?? new Jwt();
        $this->config = $config ?? Di::_()->get('Config');
        $this->secret = $this->config->get('captcha') ? $this->config->get('captcha')['jwt_secret'] : 'todo';
        $this->jwt->setKey($this->secret);
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->keyValueLimiter = $keyValueLimiter ?? Di::_()->get('Security\RateLimits\KeyValueLimiter');
        $this->bypassManager ??= new BypassManager();
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
            return $this->bypassManager->verify($captcha->getClientText());
        }

        $jwtToken = $captcha->getJwtToken();

        try {
            $this->keyValueLimiter
                ->setKey('captcha-jwt')
                ->setValue($jwtToken)
                ->setMax(1)
                ->setSeconds(300) // 5 minutes
                ->checkAndIncrement();
        } catch (RateLimitExceededException $e) {
            return false;
        }

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
