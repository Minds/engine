<?php
/**
 * Minds Translations Engine
 * @version 1
 * @author Emiliano Balbuena
 */

namespace Minds\Core\Translation;

use Minds\Core;
use Minds\Core\Translation\Services\TranslationServiceInterface;
use Minds\Entities;
use Minds\Core\Translation\Storage;
use Minds\Helpers\MagicAttributes;

class Translations
{
    protected $cache;
    /** @var TranslationServiceInterface */
    protected $service;

    const MAX_CONTENT_LENGTH = 5000;

    public function __construct($cache = null, $service = null)
    {
        $di = Core\Di\Di::_();

        $this->cache = $cache ?: $di->get('Cache');
        $this->service = $service ?: $di->get('Translation\Service');
    }

    public function translateEntity($guid, $target = null)
    {
        $storage = new Storage();

        if (!$guid) {
            return false;
        }

        if (!$target) {
            $target = 'en';
        }

        $entity = null; // Lazily-loaded if needed
        $translation = [];

        foreach (['message', 'body', 'title', 'blurb', 'description', 'question', 'answer'] as $field) {
            $stored = $storage->get($guid, $field, $target);

            if ($stored !== false) {
                // Saved in cache store
                $translation[$field] = [
                    'content' => $stored['content'],
                    'source' => $stored['source_language'],
                ];
                continue;
            }

            if (!$entity) {
                $entity = Entities\Factory::build($guid);
            }

            if (!$entity) {
                continue;
            }

            $content = '';

            switch ($field) {
                case 'question':
                    if (MagicAttributes::getterExists($entity, 'getQuestion')) {
                        $content = nl2br($this->parseMessage($entity->getQuestion()));
                    }
                    break;
                case 'answer':
                    if (MagicAttributes::getterExists($entity, 'getAnswer')) {
                        $content = nl2br($this->parseMessage($entity->getAnswer()));
                    }
                    break;
                case 'message':
                    if (method_exists($entity, 'getMessage')) {
                        $content = nl2br($this->parseMessage($entity->getMessage()));
                    } elseif (property_exists($entity, 'message') || isset($entity->message)) {
                        $content = nl2br($this->parseMessage($entity->message));
                    }
                    break;

                case 'body':
                    if (MagicAttributes::getterExists($entity, 'getBody')) {
                        $content = $entity->getBody();
                    } elseif (property_exists($entity, 'body') || isset($entity->body)) {
                        $content = $entity->body;
                    }
                    break;

                case 'description':
                    if (method_exists($entity, 'getDescription')) {
                        $content = $entity->getDescription();
                    } elseif (property_exists($entity, 'description') || isset($entity->description)) {
                        $content = $entity->description;
                    }
                    break;

                case 'title':
                    if (MagicAttributes::getterExists($entity, 'getTitle')) {
                        $content = $entity->getTitle();
                        break;
                    }
                    // no break
                case 'blurb':
                    if (!$entity->custom_type) {
                        continue 2; // exit switch AND continue foreach
                    }

                    if (property_exists($entity, $field) || isset($entity->{$field})) {
                        $content = $entity->{$field};
                    }
                    break;
            }

            if (strlen($content) === 0) {
                continue;
            }

            if (strlen($content) > static::MAX_CONTENT_LENGTH) {
                $content = substr($content, 0, static::MAX_CONTENT_LENGTH);
            }

            $translation[$field] = $this->translateText($content, $target);

            $translation[$field]['content'] = strip_tags(static::brToNewLine($translation[$field]['content']));

            if ($translation[$field]) {
                $storage->set($guid, $field, $target, $translation[$field]['source'], $translation[$field]['content']);
            }
        }

        return $translation;
    }

    public function translateText($content, $target = null, $source = null)
    {
        if (!$target) {
            $target = 'en';
        }

        return $this->service->translate($content, $target, $source);
    }

    /**
     * Converts <br > to a new line
     * @param string $value
     * @return string
     */
    private function brToNewLine(string $value): string
    {
        return preg_replace('/<br\s\/>/im', "\r\n", $value);
    }

    /**
     * Puts URLs and tags inside a <span translate="no">
     * @param string $message
     * @return string
     */
    private function parseMessage(string $message): string
    {
        $replacement = '<span translate="no">$0</span>';
        // replace URLs
        $message = preg_replace('/(\b(https?|ftp|file):\/\/[^\s\]]+)/im', $replacement, $message);
        // replace mentions
        $message = preg_replace('/(^|\W|\s)@([a-z0-9_\-\.]+[a-z0-9_])/im', $replacement, $message);

        return $message;
    }
}
