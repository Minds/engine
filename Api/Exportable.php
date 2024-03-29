<?php

/**
 * Api Exportable
 *
 * @author emi
 */

namespace Minds\Api;

use Composer\Semver\Comparator;
use Minds\Helpers\MagicAttributes;

class Exportable implements \JsonSerializable
{
    /** @var array */
    protected $items = [];

    /** @var string */
    protected $exportContext = '';

    /** @var array */
    protected $exceptions = [];

    /** @var array */
    protected $exportArgs = [];

    /**
     * Sets the items to be exported
     * @param mixed $items
     * @return Exportable
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @param string $exportContext
     * @return Exportable
     */
    public function setExportContext($exportContext)
    {
        $this->exportContext = $exportContext;
        return $this;
    }

    /**
     * @param array $exceptions
     * @return Exportable
     */
    public function setExceptions($exceptions)
    {
        $this->exceptions = $exceptions;
        return $this;
    }

    /**
     * @param array $exportArgs
     * @return Exportable
     */
    public function setExportArgs(...$exportArgs)
    {
        $this->exportArgs = $exportArgs;
        return $this;
    }

    /**
     * Exportable constructor.
     * @param null $items
     */
    public function __construct($items = [])
    {
        $this->setItems($items);
    }

    /**
     * Exports the items
     * @return array
     */
    public function export(): array
    {
        if (!$this->items || (!is_array($this->items) && !($this->items instanceof \Iterator))) {
            return [];
        }

        $output = [];
        $isSequential = isset($this->items[0]);

        foreach ($this->items as $key => $item) {
            if (!$item || !method_exists($item, 'export')) {
                if (!$isSequential) {
                    $output[$key] = null;
                }

                continue;
            }

            if (method_exists($item, 'setExportContext')) {
                $item->setExportContext($this->exportContext);
            }

            $exported = $item->export(...$this->exportArgs);

            // Shims
            // TODO: Maybe allow customization via classes? i.e. JavascriptGuidShim, ExceptionShim, etc

            if (
                $isSequential &&
                (
                    (method_exists($item, '_magicAttributes') && MagicAttributes::getterExists($item, 'isDeleted'))
                    || method_exists($item, 'isDeleted')
                ) &&
                $item->isDeleted()
            ) {
                continue;
            }

            if (isset($exported['guid'])) {
                $exported['guid'] = (string) $exported['guid'];
            }

            if (isset($exported['ownerObj']['guid'])) {
                $exported['ownerObj']['guid'] = (string) $exported['ownerObj']['guid'];
            }

            if (
                isset($exported['urn']) &&
                isset($_SERVER['HTTP_APP_VERSION']) &&
                Comparator::lessThan($_SERVER['HTTP_APP_VERSION'], '4.9.0')
            ) {
                $exported['urn'] = "urn:entity:{$exported['guid']}";
            }

            foreach ($this->exceptions as $exception) {
                $exported[$exception] = $item->{$exception};
            }

            //

            $output[$key] = $exported;
        }

        if ($isSequential) {
            $output = array_values($output);
        }

        return $output;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->export();
    }

    /**
     * Convenience constructor for Exportable.
     * @param array $items
     * @return static
     */
    public static function _($items = [])
    {
        return new self($items);
    }
}
