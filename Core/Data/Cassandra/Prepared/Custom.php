<?php
/**
 * Prepared query
 */
namespace Minds\Core\Data\Cassandra\Prepared;

use  Minds\Core\Data\Interfaces;

class Custom implements Interfaces\PreparedInterface
{
    private $template;
    private $values;
    private $opts = [];

    public function build()
    {
        return [
            'string' => $this->template,
            'values'=>$this->values
            ];
    }

    public function query($cql, $values = [])
    {
        $this->template = $cql;
        $this->values = $values;
        return $this;
    }

    public function setOpts($opts)
    {
        $this->opts = $opts;
        return $this;
    }

    public function getOpts()
    {
        return $this->opts;
    }
}
