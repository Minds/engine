<?php
/**
 * Page entity
 */
namespace Minds\Entities;

use Minds\Core;
use Minds\Core\Data;

class Page extends DenormalizedEntity
{
    protected $title;
    protected $body;
    protected $path;
    protected $menuContainer;
    protected $header;
    protected $headerTop;
    protected $subtype = 'page';
    protected $rowKey = 'pages';
    protected $exportableDefaults = [ 'title', 'body', 'path', 'menuContainer', 'header', 'headerTop', 'subtype' ];

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setPath($path)
    {
        $this->path = $path;
        $this->guid = $path;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setMenuContainer($container)
    {
        $this->menuContainer = $container;
        return $this;
    }

    public function getMenuContainer()
    {
        return $this->menuContainer;
    }

    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setHeaderTop($top)
    {
        $this->headerTop = (int) $top;
        return $this;
    }

    public function getHeaderTop()
    {
        return (int) $this->headerTop;
    }

    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
        return $this;
    }

    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * Save the entity
     * @param boolean $index
     * @return $this
     */
    public function save($index = true)
    {
        $success = $this->saveToDb([
            'title' => $this->title,
            'body' => $this->body,
            'path' => $this->path,
            'menuContainer' => $this->menuContainer,
            'header' => $this->header,
            'headerTop' => $this->headerTop,
            'subtype' => $this->subtype
        ]);
        if (!$success) {
            throw new \Exception("We couldn't save the entity to the database");
        }
        //$this->saveToIndex();
        return $this;
    }

    public function export(array $key = [])
    {
        $export = parent::export();

        $export['body'] = (string) $export['body'];

        return $export;
    }
}
