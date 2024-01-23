<?php

namespace Spec\Minds\Common\Traits;

use Iterator;
use PhpSpec\Exception\Example\FailureException;

trait CommonMatchers
{
    public function getMatchers(): array
    {
        return [
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
            },
            'beAGeneratorWithValues' => function ($subject, $items) {
                $subjectItems = iterator_to_array($subject);

                if (serialize($subjectItems) !== serialize($items)) {
                    throw new FailureException(sprintf("Subject should be a traversable containing %s, but got %s.", serialize($items), serialize($subjectItems)));
                }

                return true;
            },
            'containAnInstanceOf' => function (Iterator $subject, string $className): bool {
                foreach ($subject as $item) {
                    if (!($item instanceof $className)) {
                        return false;
                    }
                }
                return true;
            },
            'yieldAnInstanceOf' => function (Iterator $subject, string $className): bool {
                foreach ($subject as $item) {
                    if (!$item instanceof $className) {
                        return false;
                    }
                }

                return true;
            },
            'haveALengthOf' => function ($subject, $value) {
                return count($subject) === $value;
            },
            'beSameAs' => function ($subject, $value): bool {
                return serialize($subject) === serialize($value);
            },
            'completeCallback' => function ($subject, callable $callback): bool {
                return $callback($subject);
            },
        ];
    }
}
