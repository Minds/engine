<?php

namespace Spec\Minds\Common\Traits;

use PhpSpec\Exception\Example\FailureException;

trait CommonMatchers
{
    public function getMatchers(): array
    {
        return  [
            'containValueLike' => function ($subject, $value) {
                foreach ($subject as $item) {
                    if ($item == $value) {
                        return true;
                    }
                }
                return false;
            },
            'beAGenerator' => function ($subject, $items) {
                $subjectItems = iterator_to_array($subject);
    
                if ($subjectItems !== $items) {
                    throw new FailureException(sprintf("Subject should be a traversable containing %s, but got %s.", json_encode($items), json_encode($subjectItems)));
                }
    
                return true;
            }
        ];
    }
}
