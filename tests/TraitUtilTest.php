<?php

declare(strict_types=1);

namespace Atk4\Core\tests;

use Atk4\Core\HookTrait;
use Atk4\Core\NameTrait;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\TraitUtil;

class TraitUtilTest extends TestCase
{
    public function testHasTrait(): void
    {
        static::assertFalse(TraitUtil::hasTrait(TraitUtilTestA::class, NameTrait::class));
        static::assertTrue(TraitUtil::hasTrait(TraitUtilTestB::class, NameTrait::class));
        static::assertTrue(TraitUtil::hasTrait(TraitUtilTestC::class, NameTrait::class));

        static::assertFalse(TraitUtil::hasTrait(new TraitUtilTestA(), NameTrait::class));
        static::assertTrue(TraitUtil::hasTrait(new TraitUtilTestB(), NameTrait::class));
        static::assertTrue(TraitUtil::hasTrait(new TraitUtilTestC(), NameTrait::class));

        static::assertFalse(TraitUtil::hasTrait(new class() extends TraitUtilTestA {
        }, NameTrait::class));
        static::assertTrue(TraitUtil::hasTrait(new class() extends TraitUtilTestB {
        }, NameTrait::class));
        static::assertTrue(TraitUtil::hasTrait(new class() extends TraitUtilTestC {
        }, NameTrait::class));

        static::assertFalse(TraitUtil::hasTrait(TraitUtilTestA::class, HookTrait::class));
        static::assertTrue(TraitUtil::hasTrait(TraitUtilTestB::class, HookTrait::class));
        static::assertTrue(TraitUtil::hasTrait(TraitUtilTestC::class, HookTrait::class));
    }
}

class TraitUtilTestA
{
}

trait TraitUtilTestTrait
{
    use HookTrait;
}

class TraitUtilTestB extends TraitUtilTestA
{
    use NameTrait;
    use TraitUtilTestTrait;
}

class TraitUtilTestC extends TraitUtilTestB
{
}
