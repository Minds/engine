<?php

namespace Minds\Core\Helpdesk\Category;

use Minds\Core\Di\Di;
use Minds\Core\Helpdesk\Entities\Category;
use Minds\Core\Util\UUIDGenerator;
use Minds\Controllers\api\v2\notifications\follow;

class Repository
{
    /** @var \PDO */
    protected $db;

    public function __construct(\PDO $db = null)
    {
        $this->db = $db ?: Di::_()->get('Database\PDO');
    }

    /**
     * @param array $opts
     * @return Category[]
     */
    public function getAll(array $opts = [])
    {
        $opts = array_merge([
            'limit' => 10,
            'offset' => 0,
            'uuid' => '',
        ], $opts);

        $query = "SELECT * FROM helpdesk_categories as cats1";

        $where = [];
        $values = [];

        if ($opts['uuid']) {
            $where[] = 'cats1.uuid = ?';
            $values[] = $opts['uuid'];
        }

        if (count($where) > 0) {
            $query .= ' WHERE ' . implode('AND', $where);
        }

        $statement = $this->db->prepare($query);

        $statement->execute($values);

        $data = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];

        foreach ($data as $row) {
            $category = new Category();
            $category->setUuid($row['uuid'])
                ->setTitle($row['title'])
                ->setParentUuid($row['parent'])
                ->setBranch($row['branch']);

            $result[] = $category;
        }

        return $result;
    }

    /**
     * Get ona category by uuid
     *
     * @param string $uuid
     * @return Category
     */
    public function getOne($uuid)
    {
        $query = "SELECT * FROM helpdesk_categories WHERE uuid = ?";

        $statement = $this->db->prepare($query);
        $statement->execute([$uuid]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (!$row) return null;

        $category = new Category();
        $category->setUuid($row['uuid'])
            ->setTitle($row['title'])
            ->setParentUuid($row['parent'])
            ->setBranch($row['branch']);

        return $category;
    }

    /**
     * Get the categories branch given an uuid
     *
     * @param string $uuid
     * @return Catergory
     */
    public function getBranch($uuid) {
        $leaf = $this->getOne($uuid);

        if (!$leaf) return null;

        $branch = explode(':', $leaf->getBranch());
        array_pop($branch);

        $child = $leaf;
        foreach (array_reverse($branch) as $parent_uuid) {
            $parent = $this->getOne($parent_uuid);
            $child->setParent($parent);
            $child = $parent;
        }

        return $leaf;
    }

    /**
     * Add a new category
     *
     * @param Category $category
     * @return void
     */
    public function add(Category $category)
    {
        $query = "INSERT INTO helpdesk_categories(uuid, title, parent, branch) VALUES (?,?,?,?)";
        $uuid = UUIDGenerator::generate();

        $values = [
            $uuid,
            $category->getTitle(),
            $category->getParentUuid(),
            // we need to do this as cockroachdb doesn't yet support triggers
            $category->getParentUuid() ? $this->generateBranch($uuid, $category->getParentUuid()) : $uuid
        ];

        $statement = $this->db->prepare($query);

        if (!$statement->execute($values)) {
            return false;
        }

        return $uuid;
    }

    /**
     * Delete a category
     *
     * @param string $category_uuid
     * @return void
     */
    public function delete(string $category_uuid)
    {
        $query = "DELETE FROM helpdesk_categories WHERE uuid = ?";

        $values = [$category_uuid];

        try {
            $statement = $this->db->prepare($query);

            return $statement->execute($values);
        } catch (\Exception $e) {
            error_log($e);
            return false;
        }
    }

    /**
     * Generate the brach field for a category
     *
     * @param string $uuid
     * @param string $parent_uuid
     * @return string
     */
    protected function generateBranch($uuid, $parent_uuid)
    {
        $statement = $this->db->prepare('SELECT branch FROM helpdesk_categories WHERE uuid = ?');

        $statement->execute([$parent_uuid]);

        return $statement->fetchColumn().':'.$uuid;
    }
}