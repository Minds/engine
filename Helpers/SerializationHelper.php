<?php

declare(strict_types=1);

namespace Minds\Helpers;

use ReflectionClass;

/**
 * Serialization helper functions.
 */
class SerializationHelper
{
    /**
     * Get any uninitialized properties of the given parent class along with the
     * default values that should be set. Useful to handle deserialization
     * (for example from cache) without leaving newly added properties uninitialized.
     * Note that deserialization could still fail if the property is not nullable
     * and has no default value.
     *
     * Usage example:
     *
     * ```php
     * public function __wakeup(): void
     * {
     *     $propertiesToInitialize = (new SerializationHelper())->getUnititializedProperties($this);
     *     foreach ($propertiesToInitialize as $propName => $propValue) {
     *         $this->{$propName} = $propValue;
     *     }
     * }
     * ```
     * @param object $parentClass - reference to parent class.
     * @return array - array of properties to update, with their intended default value.
     */
    public function getUnititializedProperties(object $parentClass): array
    {
        $reflectedClass = new ReflectionClass($parentClass::class);
        $reflectedProperties = $reflectedClass->getProperties();
        $propertiesToUpdate = [];

        foreach ($reflectedProperties as $reflectedProperty) {
            // if the property isn't directly of this class, skip over it.
            if ($reflectedProperty->class !== $parentClass::class) {
                continue;
            }

            // if the property is initialized already, skip over it.
            if ($reflectedProperty->isInitialized($parentClass)) {
                continue;
            }

            $propName = $reflectedProperty->getName();

            // if there is a default value, set it and continue looping.
            if ($reflectedProperty->hasDefaultValue()) {
                $propertiesToUpdate[$propName] = $reflectedProperty->getDefaultValue();
                continue;
            }

            // if the property is promoted, we want to grab the param from the constructor to see whether it has a default value.
            if ($reflectedProperty->isPromoted()) {
                $reflectedConstructorParams = $reflectedClass->getConstructor()->getParameters();
                $constructorParam = array_values(array_filter($reflectedConstructorParams, function ($constructorParam) use ($propName) {
                    return $constructorParam->name === $propName;
                }))[0] ?? null;

                if ($constructorParam) {
                    $propertiesToUpdate[$propName] = $constructorParam->getDefaultValue();
                    continue;
                }
            }

            // else try to instantiate it to null.
            $propertiesToUpdate[$propName] = null;
        }

        return $propertiesToUpdate;
    }
}
