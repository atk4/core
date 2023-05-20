<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\ConfigTrait;
use Atk4\Core\Exception;
use Atk4\Core\Phpunit\TestCase;

class ConfigTraitTest extends TestCase
{
    /** @var string */
    public $dir = __DIR__ . '/config_test';

    public function testFileRead(): void
    {
        // for php
        $a = [
            'num' => 123,
            'txt' => 'foo',
            'bool' => true,
            'obj' => new \stdClass(),
            'arr' => [
                'num' => 456,
                'txt' => 'bar',
                'bool' => true,
                'obj' => new \stdClass(),
            ],
        ];
        // for json
        $b = [
            'num' => 123,
            'txt' => 'foo',
            'bool' => true,
            'obj' => [
                'num' => 456,
                'txt' => 'bar',
                'bool' => true,
            ],
            'arr' => [
                ['one' => 'one', 'another' => 'another'],
                ['two' => 'two'],
            ],
        ];
        // for yaml
        $c = [
            'num' => 123,
            'txt' => 'foo',
            'bool' => true,
            'obj' => [
                'num' => 456,
                'txt' => 'bar',
                'bool' => true,
            ],
            'arr' => [
                ['one' => 'one', 'another' => 'another'],
                ['two' => 'two'],
            ],
        ];

        // default config
        $m = new ConfigMock();
        $m->readConfig($this->dir . '/config.php', 'php');
        self::{'assertEquals'}($a, $m->getConfigProp());

        // json config
        $m = new ConfigMock();
        $m->readConfig($this->dir . '/config.json', 'json');
        self::{'assertEquals'}($b, $m->getConfigProp());

        // yaml config
        $m = new ConfigMock();
        $m->readConfig($this->dir . '/config.yml', 'yaml');
        self::{'assertEquals'}($c, $m->getConfigProp());
    }

    public function testFileReadException(): void
    {
        $this->expectException(Exception::class);
        $m = new ConfigMock();
        $m->readConfig('unknown_file.php');
    }

    public function testFileBadFormatException(): void
    {
        $this->expectException(Exception::class);
        $m = new ConfigMock();
        $m->readConfig($this->dir . '/config_bad_format.php');
    }

    public function testWrongFileFormatException(): void
    {
        $this->expectException(Exception::class);
        $m = new ConfigMock();
        $m->readConfig($this->dir . '/config.yml', 'wrong-format');
    }

    public function testSetGetConfig(): void
    {
        $a = [
            'num' => 789,
            'txt' => 'foo',
            'bool' => true,
            'obj' => null,
            'arr' => [
                'num' => 456,
                'txt' => 'qwerty',
                'bool' => true,
                'obj' => new \stdClass(),
                'name' => 'Jane',
                'sub' => [
                    'one' => 'more',
                    'two' => 'another',
                ],
                'foo' => 'bar',
            ],
            'name' => 'John',
        ];

        // default config
        $m = new ConfigMock();
        $m->readConfig($this->dir . '/config.php', 'php');

        $m->setConfig('num', 789); // overwrite
        $m->setConfig('name', 'John'); // add
        $m->setConfig([
            'obj' => null, // overwrite
            'arr/txt' => 'qwerty', // overwrite
            'arr/name' => 'Jane', // add
            'arr/sub/one' => 'more', // add in deep structure
            'arr/sub/two' => 'another', // add one more in deep structure
            'arr' => ['foo' => 'bar'], // merge arrays
        ]);
        self::{'assertEquals'}($a, $m->getConfigProp());

        // test getConfig
        self::assertSame(789, $m->getConfig('num'));
        self::assertNull($m->getConfig('unknown'));
        self::assertSame('default', $m->getConfig('unknown', 'default'));
        self::assertSame('another', $m->getConfig('arr/sub/two', 'default'));
        self::assertSame('default', $m->getConfig('arr/sub/three', 'default'));
    }

    public function testCaseGetConfigPathThatNotExists(): void
    {
        $m = new ConfigMock();
        $m->readConfig($this->dir . '/config.php', 'php');
        $excepted = $m->getConfig('arr/num/notExists');
        self::assertNull($excepted);
    }
}

class ConfigMock
{
    use ConfigTrait;

    /**
     * @return array<string, mixed>
     */
    public function getConfigProp(): array
    {
        return $this->config;
    }
}
