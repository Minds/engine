<?php
namespace Minds\Core\I18n;

use Locale;
use Minds\Common\Cookie;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Session;

/**
 * i18n Manager
 * @package Minds\Core\I18n
 */
class Manager
{
    /** @var string */
    const DEFAULT_LANGUAGE = 'en';

    /** @var Config */
    protected $config;

    /**
     * Manager constructor.
     * @param null $config
     */
    public function __construct($config = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * Gets all set-up languages
     * @return array
     */
    public function getLanguages(): array
    {
        $languages = [];
        foreach (Locales::I18N_LOCALES as $isoCode) {
            $enDisplay = Locale::getDisplayLanguage($isoCode, 'en');
            $display = Locale::getDisplayLanguage($isoCode, $isoCode);
            $languages[$isoCode] = "$display ($enDisplay)";
        }
        return $languages;
    }

    /**
     * Get the current user's language, unless overriden
     * @return string
     */
    public function getLanguage(): ?string
    {
        $user = Session::getLoggedInUser();

        $languages = array_values(array_filter([
            $_COOKIE['hl'] ?? null, // Cookie override has highest priority
            $user ? $user->getLanguage() : null, // User, if logged in, comes next
            $this->getPrimaryLanguageFromHeader(), // Then we detect from Accept-Language header
            static::DEFAULT_LANGUAGE // Then we default to English
        ], function ($language) {
            // Filter out falsy values and language codes that are not present on Locales list
            return $language && $this->isLanguage($language);
        }));

        return $languages[0] ?? null;
    }

    /**
     * Returns if the language is a valid language
     * @param string $language
     * @return bool
     */
    public function isLanguage(string $language): bool
    {
        return in_array(strtolower($language), Locales::I18N_LOCALES, true);
    }

    /**
     * Gets primary language from header, e.g. en_GB becomes just en.
     * @param {string} $language - en_GB etc.
     * @return string - returns primary language.
     */
    public function getPrimaryLanguageFromHeader(): ?string
    {
        return Locale::getPrimaryLanguage(
            Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? static::DEFAULT_LANGUAGE)
        );
    }

    /**
     * TODO: remove from router
     */
    public function serveIndex(): void
    {
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
