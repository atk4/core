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
        $this->assertFalse(TraitUtil::hasTrait(TraitUtilTestA::class, NameTrait::class));
        $this->assertTrue(TraitUtil::hasTrait(TraitUtilTestB::class, NameTrait::class));
        $this->assertTrue(TraitUtil::hasTrait(TraitUtilTestC::class, NameTrait::class));

        $this->assertFalse(TraitUtil::hasTrait(new TraitUtilTestA(), NameTrait::class));
        $this->assertTrue(TraitUtil::hasTrait(new TraitUtilTestB(), NameTrait::class));
        $this->assertTrue(TraitUtil::hasTrait(new TraitUtilTestC(), NameTrait::class));

        $this->assertFalse(TraitUtil::hasTrait(new class() extends TraitUtilTestA {
        }, NameTrait::class));
        $this->assertTrue(TraitUtil::hasTrait(new class() extends TraitUtilTestB {
        }, NameTrait::class));
        $this->assertTrue(TraitUtil::hasTrait(new class() extends TraitUtilTestC {
        }, NameTrait::class));

        $this->assertFalse(TraitUtil::hasTrait(TraitUtilTestA::class, HookTrait::class));
        $this->assertTrue(TraitUtil::hasTrait(TraitUtilTestB::class, HookTrait::class));
        $this->assertTrue(TraitUtil::hasTrait(TraitUtilTestC::class, HookTrait::class));
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
