<?php

declare(strict_types=1);

namespace Atk4\Core;

final class TraitUtil
{
    /** @var bool[className][traitName] */
    private static $_hasTraitMap = [];

    private function __construct()
    {
        // zeroton
    }

    /**
     * @param object|string $class
     */
    public static function hasTrait($class, string $traitName): bool
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        // prevent mass use for other than internal use then we can decide
        //if we want to keep support this or replace with pure interfaces
        if (!str_starts_with($traitName, 'Atk4\Core\\')) {
            throw new Exception('Core::hasTrait is not indended for use with other than \Atk4\Core\* traits.');
        }

        if (!isset(self::$_hasTraitMap[$class][$traitName])) {
            $getUsesFunc = function (string $trait) use (&$getUsesFunc): array {
                $uses = class_uses($trait);
                foreach ($uses as $use) {
                    $uses += $getUsesFunc($use);
                }

                return $uses;
            };

            $uses = [];
            foreach (array_reverse(class_parents($class) ?: []) + [-1 => $class] as $class) {
                $uses += $getUsesFunc($class);
            }
            $uses = array_unique($uses);

            self::$_hasTraitMap[$class][$traitName] = in_array($traitName, $uses, true);
        }

        return self::$_hasTraitMap[$class][$traitName];
    }
}
