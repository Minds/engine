<?php
/**
 * ResolverDelegate.
 *
 * @author emi
 */

namespace Minds\Core\Entities\Delegates;

use Minds\Common\Urn;
use Minds\Core\Entities\Resolver;

interface ResolverDelegate
{
    /**
     * @param Resolver $resolver
     * @return ResolverDelegate
     */
    public function setResolver(Resolver $resolver);

    /**
     * @param Urn $urn
     * @return boolean
     */
    public function shouldResolve(Urn $urn);

    /**
     * @param Urn[] $urns
     * @param array $opts
     * @return mixed
     */
    public function resolve(array $urns, array $opts = []);

    /**
     * @param string $urn
     * @param mixed $entity
     * @return mixed
     */
    public function map($urn, $entity);

    /**
     * @param mixed $entity
     * @return string|null
     */
    public function asUrn($entity);
}
