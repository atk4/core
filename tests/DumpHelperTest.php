<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\DumpHelper;
use Atk4\Core\Phpunit\TestCase;

class DumpHelperTest extends TestCase
{
    /**
     * @param mixed $value
     */
    private static function getRid(&$value): string
    {
        return \Closure::bind(static function () use (&$value) {
            return (new DumpHelper())->getRid($value);
        }, null, DumpHelper::class)();
    }

    public function testFindDuplicateOidsRids(): void
    {
        $makeCaseFx = static function () {
            $v = 10.5;

            return [$v, [], [
                self::getRid($v) => 1,
            ]];
        };

        [$value, $expectedDuplicateOids, $expectedDuplicateRids] = $makeCaseFx();

        $dumpHelper = new DumpHelper();
        $duplicateOids = [];
        $duplicateRids = [];
        \Closure::bind(static function () use ($dumpHelper, &$value, &$duplicateOids, &$duplicateRids) {
            $dumpHelper->findDuplicateOidsRids($value, 50, $duplicateOids, $duplicateRids);
        }, null, DumpHelper::class)();

        self::assertSame($expectedDuplicateOids, $duplicateOids);
        self::assertSame($expectedDuplicateRids, $duplicateRids);
    }
}
