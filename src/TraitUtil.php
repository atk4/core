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

        // prevent mass use for other than internal use then we can decide
        // if we want to keep support this or replace with pure interfaces
        if (!str_starts_with($traitName, 'Atk4\Core\\')) {
            throw new Exception('Core::hasTrait is not indended for use with other than \Atk4\Core\* traits');
        }

        if (!isset(self::$_hasTraitCache[$class][$traitName])) {
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

            self::$_hasTraitCache[$class][$traitName] = in_array($traitName, $uses, true);
        }

        return self::$_hasTraitCache[$class][$traitName];
    }

    // ConfigTrait - not used
    // DebugTrait - not used
    // DynamicMethodTrait - not used
    // StaticAddToTrait - not used
    // TranslatableTrait - not used

    public static function hasAppScopeTrait(object $class): bool
    {
        return self::hasTrait($class, AppScopeTrait::class);
    }

    public static function hasContainerTrait(object $class): bool
    {
        return self::hasTrait($class, ContainerTrait::class);
    }

    /**
     * Used in Factory and in ui/View only.
     */
    public static function hasDiContainerTrait(object $class): bool
    {
        return self::hasTrait($class, DiContainerTrait::class);
    }

    /**
     * Used in DynamicMethodTrait only.
     */
    public static function hasHookTrait(object $class): bool
    {
        return self::hasTrait($class, HookTrait::class);
    }

    public static function hasInitializerTrait(object $class): bool
    {
        return self::hasTrait($class, InitializerTrait::class);
    }

    public static function hasNameTrait(object $class): bool
    {
        return self::hasTrait($class, NameTrait::class);
    }

    public static function hasTrackableTrait(object $class): bool
    {
        return self::hasTrait($class, TrackableTrait::class);
    }
}
