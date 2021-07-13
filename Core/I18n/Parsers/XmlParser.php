<?php
/**
 * @author: eiennohi.
 */
namespace Minds\Core\I18n\Parsers;

/*
 * Adapted from XmlUtils from the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */
class XmlParser
{
    /**
     * Loads an XML file.
     *
     * @param string               $file             An XML file path
     * @param string|callable|null $schemaOrCallable An XSD schema file path, a callable, or null to disable validation
     *
     * @return \DOMDocument
     *
     * @throws \InvalidArgumentException When loading of XML file returns error
     * @throws XmlParsingException       When XML parsing returns any errors
     * @throws \RuntimeException         When DOM extension is missing
     */
    public static function loadFile(string $file, $schemaOrCallable = null)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException(sprintf('Resource "%s" is not a file.', $file));
        }

        if (!is_readable($file)) {
            throw new \InvalidArgumentException(sprintf('File "%s" is not readable.', $file));
        }

        $content = @file_get_contents($file);

        if ('' === trim($content)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not contain valid XML, it is empty.', $file));
        }

        try {
            return static::parse($content, $schemaOrCallable);
        } catch (\Exception $e) {
            throw new XmlParsingException(sprintf('The XML file "%s" is not valid.', $file), 0, $e->getPrevious());
        }
    }

    protected static function getXmlErrors(bool $internalErrors)
    {
        $errors = [];
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf(
                '[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ?: 'n/a',
                $error->line,
                $error->column
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $errors;
    }

    public static function parse(string $content, $schemaOrCallable = null)
    {
        if (!\extension_loaded('dom')) {
            throw new \LogicException('Extension DOM is required.');
        }

        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->validateOnParse = true;
        if (!$dom->loadXML($content, LIBXML_NONET | (\defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
            libxml_disable_entity_loader($disableEntities);

            throw new XmlParsingException(implode("\n", static::getXmlErrors($internalErrors)));
        }

        $dom->normalizeDocument();

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        foreach ($dom->childNodes as $child) {
            if (XML_DOCUMENT_TYPE_NODE === $child->nodeType) {
                throw new XmlParsingException('Document types are not allowed.');
            }
        }

        if (null !== $schemaOrCallable) {
            $internalErrors = libxml_use_internal_errors(true);
            libxml_clear_errors();

            $e = null;
            if (\is_callable($schemaOrCallable)) {
                try {
                    $valid = $schemaOrCallable($dom, $internalErrors);
                } catch (\Exception $e) {
                    $valid = false;
                }
            } elseif (!\is_array($schemaOrCallable) && is_file((string) $schemaOrCallable)) {
                $schemaSource = file_get_contents((string) $schemaOrCallable);
                $valid = @$dom->schemaValidateSource($schemaSource);
            } else {
                libxml_use_internal_errors($internalErrors);

                throw new XmlParsingException('The schemaOrCallable argument has to be a valid path to XSD file or callable.');
            }

            if (!$valid) {
                $messages = static::getXmlErrors($internalErrors);
                if (empty($messages)) {
                    throw new \Exception('The XML is not valid.', 0, $e);
                }
                throw new XmlParsingException(implode("\n", $messages), 0, $e);
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $dom;
    }
}
