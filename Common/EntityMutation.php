<?php
namespace Minds\Common;

use Minds\Entities\MutatableEntityInterface;
use Minds\Helpers;

class EntityMutation
{
    /** @var MutatableEntityInterface */
    protected $originalEntity;

    /** @var MutatableEntityInterface */
    protected $mutatedEntity;

    /** @var array */
    protected $mutatedVars = [];

    public function __construct($originalEntity)
    {
        $this->originalEntity = $originalEntity;
        $this->mutatedEntity = clone $originalEntity;
    }

    /**
     * Magic method for getter and setters.
     * @param string $name - the function name
     * @param array $args - the arguments sent to the function
     * @return self
     */
    public function __call($name, array $args = []): self
    {
        if (strpos($name, 'set', 0) === 0) {
            $attribute = preg_replace('/^set/', '', $name);
            $attribute = lcfirst($attribute);

            // Relay this change to the mutatef entity
            if (!Helpers\MagicAttributes::setterExists($this->mutatedEntity, $name)) {
                throw new \Exception("$name does not exist the entity");
            }
            $getterName = str_replace('set', 'get', $name);
            if (!Helpers\MagicAttributes::getterExists($this->mutatedEntity, $getterName)) {
                throw new \Exception("$getterName does not exist the entity");
            }

            $this->mutatedEntity->$name($args[0]);

            $mutatedVars = $this->mutatedVars;
            array_push($mutatedVars, $attribute);
            $this->mutatedVars = array_unique($mutatedVars);

            return $this;
        }

        throw new \Exception('Call to undefined function');
    }

    /**
     * Return a diff item for a var
     * @param string $var
     * @return array
     */
    protected function getDiffItem(string $var): array
    {
        $getterFuncName = 'get' . ucfirst($var);
        return [
            'original' => $this->originalEntity->$getterFuncName(),
            'mutated' => $this->mutatedEntity->$getterFuncName(),
        ];
    }

    /**
     * Returns if a value has been mutstef
     * @param string $var
     * @return bool
     */
    public function hasMutated(string $var): bool
    {
        $diffItem = $this->getDiffItem($var);
        return $diffItem['original'] !== $diffItem['mutated'];
    }

    /**
     * Returns a diff of old and new fields
     * @return array
     */
    public function getDiff() : array
    {
        $diff = [];

        foreach ($this->mutatedVars as $var) {
            $diffItem = $this->getDiffItem($var);
            if ($diffItem['original'] !== $diffItem['mutated']) {
                $diff[$var] = $diffItem;
            }
        }

        return $diff;
    }

    /**
     * Return only the changed fields and their new values
     * @return array
     */
    public function getMutatedValues(): array
    {
        $values = [];
        foreach ($this->getDiff() as $var => $diff) {
            $values[$var] = $diff['mutated'];
        }
        return $values;
    }

    /**
    * Returns the original entity
    * @return MutatableEntityInterface
    */
    public function getOriginalEntity(): MutatableEntityInterface
    {
        return $this->originalEntity;
    }

    /**
     * Returns the fully mutated entity
     * @return MutatableEntityInterface
     */
    public function getMutatedEntity(): MutatableEntityInterface
    {
        return $this->mutatedEntity;
    }
}
