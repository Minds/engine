<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\I18n;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\I18n\Loaders\XliffFileLoader;
use Minds\Core\Log\Logger;
use Symfony\Component\Translation\Translator as SymfonyTranslator;

class Translator
{
    /** @var SymfonyTranslator */
    protected $translator;

    /** @var Config */
    protected $config;

    /** @var Logger */
    protected $logger;

    public function __construct(
        $config = null,
        $translator = null,
        $logger = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->translator = $translator ?:
            new SymfonyTranslator('en', null, null, $this->config->get('development_mode') ?? false);

        $this->logger = $logger ?: Di::_()->get('Logger');

        $this->loadResources();
    }

    /**
     * Returns the Symfony's Translator instance
     * @return SymfonyTranslator
     */
    public function getTranslator(): SymfonyTranslator
    {
        return $this->translator;
    }

    /**
     * Sets the locale
     * @param string $locale
     * @return $this
     */
    public function setLocale(string $locale): Translator
    {
        $this->translator->setLocale($locale);

        return $this;
    }

    /**
     * Translates a given string
     * @param string|null $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public function trans(?string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Loads translation files
     */
    private function loadResources(): void
    {
        $this->translator->addLoader('xlf', new XliffFileLoader());
        $languages = array_keys($this->config->get('i18n')['languages']);
        foreach ($languages as $language) {
            $file = dirname(dirname(dirname(__FILE__))) . "/translations/messages.{$language}.xliff";

            if (!file_exists($file)) {
                $this->logger->warn("Localization resource not found ({$file})");
                continue;
            }

            $this->translator->addResource('xlf', $file, $language);
        }
    }
}
