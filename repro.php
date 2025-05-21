<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

class DumpHelperTest
{
    private function getRid(&$value): string
    {
        return \ReflectionReference::fromArrayElement([&$value], 0)->getId();
    }

    protected function findDuplicateRids(&$value, array &$duplicateRids): void
    {
        $rid = $this->getRid($value);
        $c = $duplicateRids[$rid] ?? 0;
        if ($c !== 0) {
            ++$duplicateRids[$rid];

            return;
        }

        $duplicateRids[$rid] = 1;
    }

    public function testFindDuplicateRids(): void
    {
        // gc_disable(); - has no effect

        $makeCaseFx = function () {
            $v = 10.5;

            return [$v, [
                $this->getRid($v) => 1,
            ]];
        };

        [$value, $expectedDuplicateRids] = $makeCaseFx();

        $duplicateRids = [];
        \Closure::bind(function () use (&$value, &$duplicateRids) {
            $this->findDuplicateRids($value, $duplicateRids);
        }, $this, self::class)();

        if ($expectedDuplicateRids !== $duplicateRids) {
            var_dump([bin2hex(array_key_first($expectedDuplicateRids)), bin2hex(array_key_first($duplicateRids))]);
            exit(1);
        }
    }
}

$test = new DumpHelperTest();
$test->testFindDuplicateRids();
