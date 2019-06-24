<?php

use atk4\core\Definition\Factory;
use atk4\core\Definition\iDefiner;
use atk4\core\Definition\Instance;
use atk4\core\tests\DefinitionFactoryMock;
use atk4\core\tests\DefinitionInstanceMock;
use atk4\core\tests\DefinitionMultipleArgumentMock;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

return [
    LoggerInterface::class        => new Instance(function (iDefiner $c) {
        return new NullLogger();
    }),
    DefinitionInstanceMock::class => new Instance(function (iDefiner $c) {
        return new DefinitionInstanceMock();
    }),
    DefinitionFactoryMock::class  => new Factory(function (iDefiner $c) {
        return new DefinitionFactoryMock();
    }),
    'TestStaticMethodInstance'    => Instance::fromClassName(DefinitionMultipleArgumentMock::class, 1, 2, 3),
    'TestStaticMethodFactory'     => Factory::fromClassName(DefinitionMultipleArgumentMock::class, 1, 2, 3),
    'NotValidFQCNForTypeCheck'    => new Instance(function (iDefiner $c) {
        return new NullLogger();
    }),
];