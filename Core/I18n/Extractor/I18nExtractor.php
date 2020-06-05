<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\I18n\Extractor;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\PhpExtractor;

class I18nExtractor extends PhpExtractor
{
    /**
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    protected function canBeExtracted(string $file)
    {
        return $this->isFile($file) && in_array(pathinfo($file, PATHINFO_EXTENSION), ['php', 'tpl'], true);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractFromDirectory($directory)
    {
        $finder = new Finder();

        return $finder->files()->name('/\.(php|tpl)')->in($directory);
    }
}
