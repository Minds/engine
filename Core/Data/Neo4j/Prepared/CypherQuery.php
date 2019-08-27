<?php
/**
 * Prepared query
 */
namespace Minds\Core\Data\Neo4j\Prepared;

use  Minds\Core\Data\Interfaces;

class CypherQuery implements Interfaces\PreparedInterface
{
    private $template;
    private $values;
    
    public function build()
    {
        return [
            'string' => $this->template,
            'values'=>$this->values
            ];
    }
    
    public function setQuery($template, $values = [])
    {
        $this->template = $template;
        $this->values = $values;
        return $this;
    }
}
