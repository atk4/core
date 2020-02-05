<?php

namespace atk4\core;

class Utils
{
    /**
     * Generates human readable caption from camelCase model class name or field names.
     *
     * This will translate 'this\\ _isNASA_MyBigBull shit_123\Foo'
     * into 'This Is NASA My Big Bull Shit 123 Foo'
     *
     * @param string $string
     *
     * @return string
     */
    public static function toReadableCaption($string)
    {
        //$s = 'this\\ _isNASA_MyBigBull shit_123\Foo';

        // first remove not allowed characters and uppercase words
        $string = ucwords(preg_replace('/[^a-z0-9]+/i', ' ', $string));

        // and then run regex to split camelcased words too
        $string = array_map('trim', preg_split('/^[^A-Z\d]+\K|[A-Z\d][^A-Z\d]+\K/', $string, -1, PREG_SPLIT_NO_EMPTY));
        
        return implode(' ', $string);
    }
    
    public static function classUsesRecursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        
        $results = [];
        
        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += self::traitUsesRecursive($class);
        }
        
        return array_unique($results);
    }
    
    public static function traitUsesRecursive($trait)
    {
        $traits = class_uses($trait);
        
        foreach ($traits as $trait) {
            $traits += self::traitUsesRecursive($trait);
        }
        
        return $traits;
    }
}
