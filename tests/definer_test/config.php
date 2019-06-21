<?php

use atk4\core\Definition\Factory;
use atk4\core\Definition\iDefiner;
use atk4\core\Definition\Instance;
use atk4\core\tests\DefinerFactoryMock;
use atk4\core\tests\DefinerInstanceMock;
use atk4\core\tests\DefinerMultipleArgumentMock;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

return [
    LoggerInterface::class     => new Instance(function (iDefiner $c) {
        return new NullLogger();
    }),
    DefinerInstanceMock::class => new Instance(function (iDefiner $c) {
        return new DefinerInstanceMock();
    }),
    DefinerFactoryMock::class  => new Factory(function (iDefiner $c) {
        return new DefinerFactoryMock();
    }),
    'TestStaticMethodInstance' => Instance::fromClassName(DefinerMultipleArgumentMock::class, 1, 2, 3),
    'TestStaticMethodFactory'  => Factory::fromClassName(DefinerMultipleArgumentMock::class, 1, 2, 3),
    'NotValidFQCNForTypeCheck' => new Instance(function (iDefiner $c) {
        return new NullLogger();
    }),
];