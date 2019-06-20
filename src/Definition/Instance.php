<?php
declare(strict_types=1);

namespace atk4\core\Definition;

class Instance extends AbstractDefinition implements iDefinition
{
    /**
     * ShortHand for classes that need only to call Constructor.
     *
     * @param string $classname             Name of the class to be instantiated
     * @param array  $constructArguments    Arguments for the __construct method
     *
     * @return Instance
     */
    public static function fromClassName(string $classname, ...$constructArguments) :Instance {
        return new Instance(function(iDefiner $c) use ($classname, $constructArguments) {
            return new $classname(...$constructArguments);
        });
    }
}
