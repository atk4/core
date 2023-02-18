<?php

declare(strict_types=1);

namespace Atk4\Core;

// !!! INTENDED TO BE REMOVED LATER - ONLY FOR TRAIT IDENTIFICATION PROPERTIES TO INTERFACES MIGRATION !!!

final class TraitUtil
{
    /** @var array<class-string, array<string, bool>> */
    private static $_hasTraitCache = [];

    private function __construct()
    {
        // zeroton
    }

    /**
     * @param object|class-string $class
     */
    public static function hasTrait($class, string $traitName): bool
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (!isset(self::$_hasTraitCache[$class][$traitName])) {
            // prevent mass use for other than internal use then we can decide
            // if we want to keep support this or replace with pure interfaces
            if (!str_starts_with($traitName, 'Atk4\Core\\')) {
                throw new Exception(self::class . '::hasTrait is not intended for use with other than Atk4\Core\* traits');
            }

            $parentClass = get_parent_class($class);
            if ($parentClass !== false && self::hasTrait($parentClass, $traitName)) {
                self::$_hasTraitCache[$class][$traitName] = true;
            } else {
                $hasTrait = false;
                foreach (class_uses($class) as $useName) {
                    if ($useName === $traitName || self::hasTrait($useName, $traitName)) {
                        $hasTrait = true;

                        break;
                    }
                }

                self::$_hasTraitCache[$class][$traitName] = $hasTrait;
            }
        }

        return self::$_hasTraitCache[$class][$traitName];
    }

    // ConfigTrait - not used
    // DebugTrait - not used
    // DynamicMethodTrait - not used
    // StaticAddToTrait - not used
    // TranslatableTrait - not used

    /**
     * @phpstan-assert-if-true TraitUtilAppScopeTrait $obj
     * @phpstan-assert-if-true int $obj->maxNameLength
     * @phpstan-assert-if-true array<string, string> $obj->uniqueNameHashes
     */
    public static function hasAppScopeTrait(object $obj): bool
    {
        return self::hasTrait($obj, AppScopeTrait::class);
    }

    /**
     * @phpstan-assert-if-true TraitUtilContainerTrait $obj
     * @phpstan-assert-if-true array<string, object> $obj->elements
     */
    public static function hasContainerTrait(object $obj): bool
    {
        return self::hasTrait($obj, ContainerTrait::class);
    }

    /**
     * Used in Factory and in ui/View only.
     *
     * @phpstan-assert-if-true TraitUtilDiContainerTrait $obj
     */
    public static function hasDiContainerTrait(object $obj): bool
    {
        return self::hasTrait($obj, DiContainerTrait::class);
    }

    /**
     * Used in DynamicMethodTrait only.
     *
     * @phpstan-assert-if-true TraitUtilHookTrait $obj
     * @phpstan-assert-if-true array<string, array<int, array<int, array{\Closure, 1?: array<int, mixed>}>>> $obj->hooks
     */
    public static function hasHookTrait(object $obj): bool
    {
        return self::hasTrait($obj, HookTrait::class);
    }

    /**
     * @phpstan-assert-if-true TraitUtilInitializerTrait $obj
     */
    public static function hasInitializerTrait(object $obj): bool
    {
        return self::hasTrait($obj, InitializerTrait::class);
    }

    /**
     * @phpstan-assert-if-true TraitUtilNameTrait $obj
     * @phpstan-assert-if-true string $obj->name
     */
    public static function hasNameTrait(object $obj): bool
    {
        return self::hasTrait($obj, NameTrait::class);
    }

    /**
     * @phpstan-assert-if-true TraitUtilTrackableTrait $obj
     * @phpstan-assert-if-true string $obj->shortName
     */
    public static function hasTrackableTrait(object $obj): bool
    {
        return self::hasTrait($obj, TrackableTrait::class);
    }
}
