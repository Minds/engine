<?php
/**
 * Helpdesk Categories Manager
 */

namespace Minds\Core\Helpdesk\Category;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Translation\Translations;
use Minds\Entities\User;

class Manager
{
    /** @var Repository */
    private $repository;

    /** @var Translations */
    private $translations;

    /** @var User */
    private $user;

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function __construct($repository = null, $translations = null)
    {
        $this->repository = $repository ?: Di::_()->get('Helpdesk\Category\Repository');
        $this->translations = $translations ?: new Translations();
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getAll(array $opts = [])
    {
        $opts = array_merge([
            'limit' => 10,
            'offset' => 0,
            'uuid' => '',
            'recursive' => false,
        ], $opts);
        return $this->translateList($this->repository->getList($opts));
    }

    public function get($uuid)
    {
        return $this->translateSingle($this->repository->get($uuid));
    }

    public function getBranch($uuid)
    {
        return $this->translateSingle($this->repository->getBranch($uuid));
    }

    public function add(Category $category)
    {
        return $this->repository->add($category);
    }

    public function delete(string $category_uuid)
    {
        return $this->repository->delete($category_uuid);
    }

    private function translateList(Response $list)
    {
        if (!$this->user) {
            return $list;
        }
        return $list->map(function ($item) {
            return $this->translateSingle($item);
        });
    }

    private function translateSingle(Category $category)
    {
        if ($this->user && $this->user->getLanguage() !== 'en') {
            $translation = $this->translations->translateEntity($category, $this->user->getLanguage());

            $category
                ->setTitle($translation['title']['content']);
        }
        return $category;
    }
}
