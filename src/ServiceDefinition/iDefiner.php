<?php

declare(strict_types=1);

namespace atk4\core\ServiceDefinition;

use atk4\core\Exception;

interface iDefiner
{
    /**
     * Get Config Element or iDependency Object.
     *
     * @param string     $fqcn           Fully Qualified Class Name
     * @param mixed|null $default_object Object to be used as default
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function getService(string $fqcn, $default_object = null);
}
