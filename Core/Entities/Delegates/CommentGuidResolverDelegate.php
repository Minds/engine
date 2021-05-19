<?php
/**
 * @author: Marcelo
 */

namespace Minds\Core\Entities\Delegates;

use Minds\Common\Urn;
use Minds\Core\Comments\Comment;
use Minds\Core\Comments\Manager;
use Minds\Core\Di\Di;

class CommentGuidResolverDelegate implements ResolverDelegate
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * CommentGuidResolverDelegate constructor.
     * @param Manager $manager
     */
    public function __construct($manager = null)
    {
        $this->manager = $manager;
    }

    /**
     * @param Urn $urn
     * @return boolean
     */
    public function shouldResolve(Urn $urn)
    {
        return $urn->getNid() === 'comment';
    }

    /**
     * @param array $urns
     * @param array $opts
     * @return mixed
     */
    public function resolve(array $urns, array $opts = [])
    {
        $entities = [];

        foreach ($urns as $urn) {
            $comment = $this->getManager()->getByUrn($urn);

            $entities[] = $comment;
        }

        return $entities;
    }

    /**
     * @param $urn
     * @param Comment $entity
     * @return mixed
     */
    public function map($urn, $entity)
    {
        return $entity;
    }

    /**
     * @param Comment $entity
     * @return string|null
     */
    public function asUrn($entity)
    {
        if (!$entity) {
            return null;
        }

        return $entity->getUrn();
    }

    /**
     * Why do we do this? Because of circular dependencies
     * The manager has a delegate which posts to the ActionEventsTopic,
     * which calls this resolver
     * @return Manager
     */
    protected function getManager(): Manager
    {
        if (!$this->manager) {
            $this->manager = new Manager();
        }
        return $this->manager;
    }
}
