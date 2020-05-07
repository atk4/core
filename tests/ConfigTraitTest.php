<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AtkPhpunit;
use atk4\core\ConfigTrait;

/**
 * @coversDefaultClass \atk4\core\ConfigTrait
 */
class ConfigTraitTest extends AtkPhpunit\TestCase
{
    public $dir = __DIR__ . '/config_test/';

    /**
     * Test file reader.
     */
    public function testFileRead()
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
        $m->readConfig($this->dir . 'config.php', 'php');
        $this->{'assertEquals'}($a, $this->getProtected($m, 'config'));

        // inline config
        $m = new ConfigMock();
        $m->readConfig($this->dir . 'config-inline.php', 'php-inline');
        $this->{'assertEquals'}($a, $this->getProtected($m, 'config'));

        // json config
        $m = new ConfigMock();
        $m->readConfig($this->dir . 'config.json', 'json');
        $this->{'assertEquals'}($b, $this->getProtected($m, 'config'));

        // yaml config
        $m = new ConfigMock();
        $m->readConfig($this->dir . 'config.yml', 'yaml');
        //var_dump($this->getProtected($m, 'config'));
        $this->{'assertEquals'}($c, $this->getProtected($m, 'config'));
    }

    public function testFileReadException()
    {
        $this->expectException(\atk4\core\Exception::class);
        $m = new ConfigMock();
        $m->readConfig('unknown_file.php');
    }

    public function testFileBadFormatException()
    {
        $this->expectException(\atk4\core\Exception::class);
        $m = new ConfigMock();
        $m->readConfig($this->dir . 'config_bad_format.php');
    }

    public function testWrongFileFormatException()
    {
        $this->expectException(\atk4\core\Exception::class);
        $m = new ConfigMock();
        $m->readConfig($this->dir . 'config.yml', 'wrong-format');
    }

    public function testSetGetConfig()
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
        $m->readConfig($this->dir . 'config.php', 'php');

        $m->setConfig('num', 789);       // overwrite
        $m->setConfig('name', 'John');   // add
        $m->setConfig([
            'obj' => null,          // overwrite
            'arr/txt' => 'qwerty',      // overwrite
            'arr/name' => 'Jane',        // add
            'arr/sub/one' => 'more',        // add in deep structure
            'arr/sub/two' => 'another',     // add one more in deep structure
            'arr' => ['foo' => 'bar'], // merge arrays
        ]);
        $this->{'assertEquals'}($a, $this->getProtected($m, 'config'));

        // test getConfig
        $this->assertSame(789, $m->getConfig('num'));
        $this->assertNull($m->getConfig('unknown'));
        $this->assertSame('default', $m->getConfig('unknown', 'default'));
        $this->assertSame('another', $m->getConfig('arr/sub/two', 'default'));
        $this->assertSame('default', $m->getConfig('arr/sub/three', 'default'));
    }

    public function testCaseGetConfigPathThatNotExists()
    {
        $m = new ConfigMock();
        $m->readConfig($this->dir . 'config.php', 'php');
        $excepted = $m->getConfig('arr/num/notExists');
        $this->assertNull($excepted);
    }
}

// @codingStandardsIgnoreStart
class ConfigMock
{
    use ConfigTrait;
}
// @codingStandardsIgnoreEnd
