<?php
/**
 * A boolean.
 *
 * @package    Elgg.Core
 * @subpackage XMLRPC
 */
class XMLRPCBoolParameter extends XMLRPCParameter
{
    /**
     * New bool parameter
     *
     * @param bool $value Value
     */
    public function __construct($value)
    {
        parent::__construct();

        $this->value = (bool)$value;
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString()
    {
        $code = ($this->value) ? "1" : "0";
        return "<value><boolean>{$code}</boolean></value>";
    }
}
