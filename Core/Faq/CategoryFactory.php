<?php
/**
 * FAQ Category Factory
 */
namespace Minds\Core\Faq;

class CategoryFactory
{
    protected static $categories = [];

    public static function _($id)
    {
        $id = strtolower($id);
        if (isset(static::$categories[$id])) {
            return static::$categories[$id];
        }

        $category = new Category();
        $category->setCategory($id);

        return static::$categories[$id] = $category;
    }
}
