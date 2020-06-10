<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Core\Analytics\EntityCentric\Manager;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Entities;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Symfony\Component\Translation\Catalogue\TargetOperation;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\Extractor\PhpExtractor;
use Symfony\Component\Translation\MessageCatalogue;

class I18nExtractor extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $locale = 'en';
        $dry = $this->getOpt('dry') ?? false;

        /** @var Core\Config\Config $config */
        $config = Core\Di\Di::_()->get('Config');

        if ($dry) {
            $this->out('Running in dry mode. Templates will be shown in the console');
        }

        // get translator

        /** @var Core\I18n\Translator $translator */
        $translator = Core\Di\Di::_()->get('I18n\Translator');

        $translator->setLocale($locale);

        // fetch all files that could use i18n
        $files = $this->getFiles();

        /** @var MessageCatalogue $sourceCatalogue */
        $sourceCatalogue = $translator->getTranslator()->getCatalogue();

        $updatedMessages = $this->extract($files, $sourceCatalogue);

        $newCatalogue = new MessageCatalogue($locale, $updatedMessages);

        $xliff = $this->dump($newCatalogue);

        if ($dry) {
            $this->out($xliff);
        } else {
            $this->updateFile($xliff);
        }
    }

    /**
     * Returns a list of all translatable files in /engine
     * @return array
     */
    private function getFiles(): array
    {
        $files = [];
        $directory = new \RecursiveDirectoryIterator(getcwd());
        $iterator = new \RecursiveIteratorIterator($directory);

        $ignores = [
            '\.git',
            '\/engine\/vendor',
            '\/engine\/lib',
            '\/engine\/classes',
            '\/php-meminfo',
            '\/engine\/Spec',
            '\/Controllers\/Cli',
            '\/coverage',
            '\/containers',
            '\/\.',
            '\/(\.){2}',
            'settings\.php',
            'settings\.example\.php',
        ];

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $regex = '[' .implode('|', $ignores) . ']';
            if (preg_match($regex, $file->getPathname())) {
                continue;
            }

            // ignore all files that aren't .php or .tpl
            if (!strpos($file->getFilename(), '.php') && !strpos($file->getFilename(), '.tpl')) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }

    /**
     * Extracts all found messages in php templates
     * @param array $files
     * @param MessageCatalogue $sourceCatalogue
     * @return array
     */
    private function extract(array $files, MessageCatalogue $sourceCatalogue): array
    {
        $extractor = new Core\I18n\Extractor\I18nExtractor();

        // new catalogue
        $targetCatalogue = new MessageCatalogue($sourceCatalogue->getLocale());

        // extract translations from found files
        $extractor->extract($files, $targetCatalogue);

        // merge new translations with the ones we already have in our xliff files
        $operation = new TargetOperation($sourceCatalogue, $targetCatalogue);

        $newMessages = $operation->getMessages('messages');

        return ['messages' => $newMessages];
    }

    /**
     * Generates the xliff template
     * @param MessageCatalogue $catalogue
     * @return string
     */
    private function dump(MessageCatalogue $catalogue): string
    {
        $dumper = new XliffFileDumper();

        return $dumper->formatCatalogue($catalogue, 'messages');
    }

    /**
     * Updates xliff files
     * @param string $template
     * @param string $locale
     */
    private function updateFile(string $template)
    {
        file_put_contents(getcwd() . "/translations/messages.en.xliff", $template);
    }
}
