<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Phpunit\TestCase;

class DumpHelperTest extends TestCase
{
    /**
     * @param mixed $value
     */
    private function getRid(&$value): string
    {
        return \ReflectionReference::fromArrayElement([&$value], 0)->getId();
    }

    /**
     * @param mixed                       $value
     * @param array<string, positive-int> $duplicateRids
     */
    protected function findDuplicateRids(&$value, int $maxDepth, array &$duplicateRids, int $depth = 0): void
    {
        $rid = $this->getRid($value);

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
        $this->findDuplicateRids($value, 50, $duplicateRids);

        self::assertSame($expectedDuplicateRids, $duplicateRids);
    }
}
