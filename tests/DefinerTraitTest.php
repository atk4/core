<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\DefinerTrait;
use atk4\core\Definition\iDefiner;
use atk4\core\DefinitionTrait;

/**
 * @coversDefaultClass  \atk4\core\DefinerTrait
 */
class DefinerTraitTest extends DefinitionTraitTest
{
    public $dir = __DIR__.'/definer_test/';

    /** @var iDefiner */
    public $mock;

    /**
     * this will throw an exception if there is some error in loading.
     */
    public function setUp()
    {
        $app = new DefinerMock();
        $app->readConfig($this->dir.DIRECTORY_SEPARATOR.'config.php','php-inline');

        $this->mock = $app;
    }
}