<?php
namespace Minds\Core\Categories;

use Minds\Core;
use Minds\Core\Di\Di;

class Repository
{

    private $db;
    private $config;

    private $filter;
    private $type;
    private $categories;

    public function __construct($db = NULL, $config = NULL)
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
        $this->config = $config ?: Di::_()->get('Config');
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setCategories($categories)
    {
          $availableCategories = $this->config->get('categories');
          //sanitize these categories
          foreach ($categories as $category) {
              if (isset($availableCategories[$category])) {
                  $this->categories[] = $category;
              }
          }
          return $this;
    }

    public function getCategories()
    {
        if (empty($this->categories)) {
            return array_keys($this->config->get('categories'));
        }
        return $this->categories;
    }

    public function get(array $opts = [])
    {
        $opts = array_merge([
          'limit' => 10,
          'offset' => ''
        ], $opts);

        $query = new Core\Data\Cassandra\Prepared\Custom();
        $query->query("SELECT * FROM categories
          WHERE type = ?
          AND filter = ?
          AND category IN ?
          ALLOW FILTERING", [
            $this->type,
            $this->filter,
            \Cassandra\Type::collection(\Cassandra\Type::text())->create(... $this->getCategories())
          ]);
        try {
            $result = $this->db->request($query);
            $guids = [];
            foreach ($result as $row) {
                $guids[] = $row['guid'];
            }
            return $guids;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function add($guid)
    {
        $query = new Core\Data\Cassandra\Prepared\Custom();
        if (empty($this->categories)) {
            return $this;
        }
        foreach ($this->categories as $category) {
            $query->query("INSERT INTO categories
              (type, category, filter, guid)
              VALUES (?, ?, ?, ?)",
              [ $this->type, $this->filter, $category, (string) $guid ]);
            try {
                $result = $this->db->request($query);
            } catch (\Exception $e) { }
        }
        return $this;
    }

    public function remove($guid, $category)
    {
        $query = new Core\Data\Cassandra\Prepared\Custom();
        if (empty($this->categories)) {
            return $this;
        }
        foreach ($this->categories as $category) {
            $query->query("DELETE FROM categories
              WHERE type = ? AND category = ? AND filter = ? AND guid = ?",
              [ $this->type, $this->filter, $category, (string) $guid ]);
            try {
                $result = $this->db->request($query);
            } catch (\Exception $e) { }
        }
        return $this;
    }

}
