<?php

declare(strict_types=1);

namespace atk4\core;

interface DiContainerInterface
{
    /**
     * Call from __construct() to initialize the properties allowing
     * developer to pass Dependency Injector Container.
     *
     * @param bool $passively If true, existing non-null argument values will be kept
     *
     * @return $this
     */
    public function setDefaults(array $properties, bool $passively = false);

    /**
     * Sets object property.
     * Throws exception.
     *
     * @param mixed $key
     * @param mixed $value
     * @param bool  $strict
     *
     * @return $this
     */
    protected function setMissingProperty($key, $value);
}
