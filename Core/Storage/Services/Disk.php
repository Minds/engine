<?php

namespace Minds\Core\Storage\Services;

class Disk implements ServiceInterface
{

    private $filepath;
    public $resource; //filepointer
    protected $redirect = false;

    public function open($path, $mode)
    {
        switch ($mode) {
            case "write":
                $mode = "w+b";
                break;
            case "read":
                $mode = "rb";
                break;
            case "redirect":
                $mode = "rb";
                $this->redirect = true;
                break;
        }

        $this->resource = fopen($path, $mode);
        return $this;
    }

    public function close()
    {
        return fclose($this->resource);
    }

    public function write($data)
    {
        return fwrite($this->resource, $data);
    }

    public function read($length = 0)
    {
        if(!$this->resource){
            return false;
        }

        if (!$length) {
            $stat = fstat($this->resource);
            $length = $stat['size'];
        }

        if ($this->redirect) {
            rewind($this->resource);
            fpassthru($this->resource);
            exit;
        }

        return fread($this->resource, $length);
    }

    public function seek($offset = 0)
    {
        return fseek($this->resource, $offset);
    }

    public function destroy()
    {

    }


}
