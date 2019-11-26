<?php
/**
 * @author: Marcelo
 */

namespace Minds\Core\Entities\Delegates;

use Minds\Common\Urn;
use Minds\Core\Boost\Repository;
use Minds\Core\Comments\Comment;
use Minds\Core\Comments\Manager;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Boost\BoostEntityInterface;

class CommentGuidResolverDelegate implements ResolverDelegate
{
    /** @var Resolver */
    protected $resolver;

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
        $this->manager = $manager ?: new Manager();
    }

    /**
     * @param Resolver $resolver
     * @return CommentGuidResolverDelegate
     */
    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;
        return $this;
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
            /** @var Comment $comment */
            $comment = $this->manager->getByUrn($urn);

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
}
