<?php
declare(strict_types=1);

namespace Minds\Core\Search\Helpers;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;

/**
 * Inject exact matches into search results.
 */
class DirectMatchInjector
{
    public function __construct(private ?EntitiesBuilder $entitiesBuilder = null)
    {
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
    }

    /**
     * Injects a direct match for the user query if it exists and is not in results.
     * If it is already in results, will move it to the top.
     * @param array $entities - The search results.
     * @param string $query - The search query.
     * @return array - returns modified array.
     */
    public function injectDirectUserMatch(array $entities, string $query): array
    {
        $existingMatchIndex = null;

        for ($i = 0; $i < count($entities); $i++) {
            if (isset($entities[$i]['username']) && strtolower($entities[$i]['username']) === strtolower($query)) {
                $existingMatchIndex = $i;
                break;
            }
        }

        if (!is_numeric($existingMatchIndex)) {
            // If the query has no exact match, search for one and prepend to top of the list if it exists.
            if ($exactMatch = $this->entitiesBuilder->getByUserByIndex($query)) {
                $entities = array_merge([$exactMatch->export()], $entities);
            }
        } elseif ($existingMatchIndex > 0) {
            // If the query has an exact match that is not at the top, move it to the top of the list.
            $directMatch = $entities[$i];
            unset($entities[$i]);
            array_unshift($entities, $directMatch);
        }

        return $entities;
    }
}
