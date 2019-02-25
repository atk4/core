<?php

namespace atk4\core\tests;

use atk4\core\ConfigTrait;

/**
 * @coversDefaultClass \atk4\core\ConfigTrait
 */
class ConfigTraitTest extends \atk4\core\PHPUnit_AgileTestCase
{
    public $dir = __DIR__.'/config_test/';

    /**
     * Test file reader.
     */
    public function testFileRead()
    {
        // for php
        $a = [
            'num'  => 123,
            'txt'  => 'foo',
            'bool' => true,
            'obj'  => new \stdClass(),
            'arr'  => [
                'num'  => 456,
                'txt'  => 'bar',
                'bool' => true,
                'obj'  => new \stdClass(),
            ],
        ];
        // for json
        $b = [
            'num'  => 123,
            'txt'  => 'foo',
            'bool' => true,
            'obj'  => [
                'num'  => 456,
                'txt'  => 'bar',
                'bool' => true,
            ],
            'arr' => [
                ['one'  => 'one', 'another' => 'another'],
                ['two'  => 'two'],
            ],
        ];
        // for yaml
        $c = [
            'num'  => 123,
            'txt'  => 'foo',
            'bool' => true,
            'obj'  => [
                'num'  => 456,
                'txt'  => 'bar',
                'bool' => true,
            ],
            'arr' => [
                ['one'  => 'one', 'another' => 'another'],
                ['two'  => 'two'],
            ],
        ];

        // default config
        $m = new ConfigMock();
        $m->readConfig($this->dir.'config.php', 'php');
        $this->assertEquals($a, $this->getProtected($m, 'config'));

        // inline config
        $m = new ConfigMock();
        $m->readConfig($this->dir.'config-inline.php', 'php-inline');
        $this->assertEquals($a, $this->getProtected($m, 'config'));

        // json config
        $m = new ConfigMock();
        $m->readConfig($this->dir.'config.json', 'json');
        $this->assertEquals($b, $this->getProtected($m, 'config'));

        // yaml config
        $m = new ConfigMock();
        $m->readConfig($this->dir.'config.yml', 'yaml');
        //var_dump($this->getProtected($m, 'config'));
        $this->assertEquals($c, $this->getProtected($m, 'config'));
    }

    /**
     * @expectedException Exception
     */
    public function testFileReadException()
    {
        $m = new ConfigMock();
        $m->readConfig('unknown_file.php');
    }

    public function testSetGetConfig()
    {
        $a = [
            'num'  => 789,
            'txt'  => 'foo',
            'bool' => true,
            'obj'  => null,
            'arr'  => [
                'num'  => 456,
                'txt'  => 'qwerty',
                'bool' => true,
                'obj'  => new \stdClass(),
                'name' => 'Jane',
                'sub'  => [
                    'one' => 'more',
                    'two' => 'another',
                ],
                'foo'  => 'bar',
            ],
            'name' => 'John',
        ];

        // default config
        $m = new ConfigMock();
        $m->readConfig($this->dir.'config.php', 'php');

        $m->setConfig('num', 789);       // overwrite
        $m->setConfig('name', 'John');   // add
        $m->setConfig([
            'obj'         => null,          // overwrite
            'arr/txt'     => 'qwerty',      // overwrite
            'arr/name'    => 'Jane',        // add
            'arr/sub/one' => 'more',        // add in deep structure
            'arr/sub/two' => 'another',     // add one more in deep structure
            'arr'         => ['foo'=>'bar'], // merge arrays
        ]);
        $this->assertEquals($a, $this->getProtected($m, 'config'));

        // test getConfig
        $this->assertEquals(789, $m->getConfig('num'));
        $this->assertEquals(null, $m->getConfig('unknown'));
        $this->assertEquals('default', $m->getConfig('unknown', 'default'));
        $this->assertEquals('another', $m->getConfig('arr/sub/two', 'default'));
        $this->assertEquals('default', $m->getConfig('arr/sub/three', 'default'));
    }
}

// @codingStandardsIgnoreStart
class ConfigMock
{
    use ConfigTrait;
}
// @codingStandardsIgnoreEnd
