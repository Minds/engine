<?php

namespace Minds\Core\Experiments\Hypotheses;

use Minds\Core\Experiments\Bucket;

class Homepage121119 implements HypothesisInterface
{
    /**
     * Return the id for the hypothesis
     * @return string
     */
    public function getId()
    {
        return "Homepage121119";
    }

    /**
     * Return the buckets for the hypothesis
     * @return Bucket[]
     */
    public function getBuckets()
    {
        return [
            (new Bucket)
                ->setId('base')
                ->setWeight(50),
            (new Bucket)
                ->setId('form')
                ->setWeight(50),
        ];
    }
}
