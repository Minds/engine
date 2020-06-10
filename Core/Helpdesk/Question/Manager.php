<?php

namespace Minds\Core\Helpdesk\Question;

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

        return $this;
    }

    public function __construct($repository = null, $translations = null)
    {
        $this->repository = $repository ?: Di::_()->get('Helpdesk\Question\Repository');
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
            'offset' => '',
            'category_uuid' => null,
            'question_uuid' => null,
        ], $opts);
        return $this->translate($this->repository->getList($opts));
    }

    public function getTop(array $opts = [])
    {
        $opts = array_merge([
            'limit' => 8,
        ], $opts);
        return $this->repository->top($opts);
    }

    public function suggest(array $opts = [])
    {
        $opts = array_merge([
            'limit' => 10,
            'offset' => 0,
            'q' => '',
        ], $opts);
        return $this->repository->suggest($opts);
    }

    public function get($uuid)
    {
        return $this->translateSingle($this->repository->get($uuid));
    }

    public function add(Question $entity)
    {
        return $this->repository->add($entity);
    }

    public function update(Question $entity)
    {
        return $this->repository->update($entity);
    }

    public function delete($uuid)
    {
        return $this->repository->delete($uuid);
    }

    private function translate(Response $list)
    {
        if (!$this->user) {
            return $list;
        }
        return $list->map(function ($item) {
            return $this->translateSingle($item);
        });
    }

    private function translateSingle(Question $question)
    {
        if ($this->user && $this->user->getLanguage() !== 'en') {
            $translation = $this->translations->translateEntity($question, $this->user->getLanguage());

            $question
                ->setQuestion($translation['question']['content'])
                ->setAnswer($translation['answer']['content']);
        }
        return $question;
    }
}
