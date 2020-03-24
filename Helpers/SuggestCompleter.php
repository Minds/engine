<?php
namespace Minds\Helpers;

class SuggestCompleter
{
    /**
     * Build map
     * @param array $values
     * @return array
     */
    public function build($values): array
    {
        // Modify initial input text
        foreach ($values as $value) {
            // Convert & to and
            if (strpos($value, '&') !== false) {
                $values[] = str_replace('&', 'and', $value);
            }
            if (strpos($value, '\'') !== false) {
                $values[] = str_replace('\'', '', $value);
            }
        }

        // Split camel case to words
        foreach ($values as $value) {
            $split = preg_split('/([\s])?(?=[A-Z])/', $value, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($this->permutateInputs($split) as $input) {
                $values[] = $input;
            }
        }

        // Permutate strings
        foreach ($values as $value) {
            foreach ($this->permutateString($value) as $input) {
                $values[] = $input;
            }
        }

        $inputs = array_values(
            array_unique(
                array_map(function ($value) {
                    return (string) utf8_encode($value);
                //return (string) preg_replace("/[^a-zA-Z0-9\s]+/", "", $value);
                }, $values)
            )
        );
        
        $weight = count($inputs) === 1 ? 4 : 2;

        $map = [
            'input' => $inputs,
            'weight' => $weight
        ];
        return $map;
    }
    
    /**
     * @param $inputs
     * @param int $calls
     * @return array
     */
    protected function permutateInputs($inputs, $calls = 0)
    {
        if (count($inputs) <= 1 || count($inputs) >= 4 || $calls > 5) {
            return $inputs;
        }

        $result = [];
        foreach ($inputs as $key => $item) {
            foreach ($this->permutateInputs(array_diff_key($inputs, [$key => $item]), $calls++) as $p) {
                $result[] = "$item $p";
            }
        }

        return $result;
    }

    /**
     * @param $inputs
     * @param int $calls
     * @return array
     */
    protected function permutateString($string, $calls = 0)
    {
        $parts = explode(' ', $string);
        $lr = [];
        $rl = [];

        foreach ($parts as $part) {
            $lr[] = end($lr) . "$part ";
        }

        foreach (array_reverse($parts) as $part) {
            $rl[] = "$part " . end($rl);
        }

        $result = array_merge($lr, $rl);

        return $result;
    }
}
