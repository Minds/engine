<?php
namespace Minds\Core\I18n;

use Locale;
use Minds\Common\Cookie;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Session;

class I18n
{
    const DEFAULT_LANGUAGE = 'en';
    const DEFAULT_LANGUAGE_NAME = 'English';

    /** @var Config */
    protected $config;

    public function __construct($config = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * Gets all set-up languages
     * @return array
     */
    public function getLanguages()
    {
        $languages = $this->config->get('i18n')['languages'] ?: [
            static::DEFAULT_LANGUAGE => static::DEFAULT_LANGUAGE_NAME
        ];

        return $languages;
    }

    /**
     * Get the current user's language, unless overriden
     * @return string
     */
    public function getLanguage()
    {
        $user = Session::getLoggedInUser();

        if (!$user) {
            return $this->getLanguageFromHeader() ?: static::DEFAULT_LANGUAGE;
        }

        return $user->getLanguage()
            ?? $this->getPrimaryLanguageFromHeader()
            ?? static::DEFAULT_LANGUAGE;
    }

    /**
     * Returns if the language is a valid language
     * @param string $language
     * @return bool
     */
    public function isLanguage($language)
    {
        return isset($this->getLanguages()[$language]);
    }

    /**
     * Gets the language from the query string, if valid
     * @return null|string
     */
    public function getLanguageFromQueryString()
    {
        if (!isset($_GET['hl']) || !$this->isLanguage($_GET['hl'])) {
            return null;
        }

        return strtolower($_GET['hl']);
    }

    /**
     * Gets the language from the header, if valid
     * @return null|string
     */
    public function getLanguageFromHeader(): string
    {
        return Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    /**
     * Gets primary language, e.g. en_GB becomes just en.
     * @param {string} $language - en_GB etc.
     * @return string - returns primary language.
     */
    public function getPrimaryLanguageFromHeader(): string
    {
        return Locale::getPrimaryLanguage($this->getLanguageFromHeader());
    }

    /**
     * Serves the corresponding index.php file
     */
    public function serveIndex()
    {
        $dist = realpath(__MINDS_ROOT__ . '/../front/dist');
        $language = $this->getLanguage();
        $defaultLanguage = static::DEFAULT_LANGUAGE;

        $file = "{$dist}/{$language}/index.php";
        
        if (!file_exists($file)) {
            $file = "{$dist}/{$defaultLanguage}/index.php";
        }

        include($file);
    }

    /**
     * Sets the language cookie.
     * @param string $language - the value of the cookie.
     * @return void
     */
    public function setLanguageCookie(string $language): void
    {
        $cookie = new Cookie();
        $cookie
            ->setName('hl')
            ->setValue($language)
            ->setExpire(strtotime('+1 year'))
            ->setPath('/')
            ->setHttpOnly(false)
            ->create();

        $_COOKIE['hl'] = $language;
    }
}
