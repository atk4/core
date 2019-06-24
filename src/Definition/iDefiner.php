<?php
declare(strict_types=1);

namespace atk4\core\Definition;

use atk4\core\Exception;

interface iDefiner
{
    /**
     * Get Config Element or iDependency Object.
     *
     * @param string $path
     * @param mixed  $default_value
     * @param bool   $check_type
     *
     * @throws Exception
     * @return mixed
     */
    public function getDefinition(string $path, $default_value = null, bool $check_type = false);
}
