<?php

use atk4\core\ServiceDefinition\Factory;
use atk4\core\ServiceDefinition\iDefiner;
use atk4\core\ServiceDefinition\Instance;
use atk4\core\tests\FactoryServiceMultipleArgumentMock;
use atk4\core\tests\InstanceServiceMultipleArgumentMock;
use atk4\core\tests\ServiceFactoryMock;
use atk4\core\tests\ServiceInstanceMock;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

return [
    LoggerInterface::class     => new Instance(function (iDefiner $c) {
        return new NullLogger();
    }),
    ServiceInstanceMock::class => new Instance(function (iDefiner $c) {
        return new ServiceInstanceMock();
    }),
    ServiceFactoryMock::class  => new Factory(function (iDefiner $c) {
        return new ServiceFactoryMock();
    }),
    InstanceServiceMultipleArgumentMock::class => Instance::fromClassName(InstanceServiceMultipleArgumentMock::class, 1, 2, 3),
    FactoryServiceMultipleArgumentMock::class  => Factory::fromClassName(FactoryServiceMultipleArgumentMock::class, 1, 2, 3),
    'NotValidFQCNForTypeCheck'                 => new Instance(function (iDefiner $c) {
        return new NullLogger();
    }),
];
