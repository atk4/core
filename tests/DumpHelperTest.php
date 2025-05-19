<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Phpunit\TestCase;

class DumpHelperTest extends TestCase
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
        $makeCaseFx = function () {
            $v = 10.5;

            return [$v, [
                $this->getRid($v) => 1,
            ]];
        };

        [$value, $expectedDuplicateRids] = $makeCaseFx();

        $duplicateRids = [];
        $this->findDuplicateRids($value, $duplicateRids);

        self::assertSame($expectedDuplicateRids, $duplicateRids);
    }
}
