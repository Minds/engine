<?php

/**
 * ElasticSearch Search
 *
 * @author emi
 */

namespace Minds\Core\Data\ElasticSearch\Prepared;

use Minds\Core\Data\Interfaces\PreparedMethodInterface;

class Search implements PreparedMethodInterface
{
    protected $_query;
    private $method = 'search';

    /**
     * @param array $params
     */
    public function query(array $params)
    {
        $this->_query = $params;
    }

    /**
     * Build the prepared request
     * @return array
     */
    public function build()
    {
        return $this->_query;
    }

    /**
     * Return options for the query
     */
    public function getOpts()
    {
    }

    /**
     * Gets the prepared method
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Sets the prepared method.
     * @param string $method - method of search e.g 'search', 'count'.
     * @return Search for chaining.
     */
    public function setMethod(string $method): Search
    {
        $this->method = $method;
        return $this;
    }
}
