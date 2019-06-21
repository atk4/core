<?php
declare(strict_types=1);

namespace atk4\core;

use atk4\core\Definition\iDefiner;

trait DefinitionTrait
{
    /**
     * Get Config Element or iDependency Object.
     *
     * @param string     $path
     * @param mixed|null $default_value
     * @param bool       $check_type
     *
     * @return mixed
     * @throws Exception
     */
    public function getDefinition(string $path, $default_value = NULL, bool $check_type = false)
    {
        // if an app is defined, call the definer method
        if (isset($this->app)) {

            if (($app = $this->app) instanceof iDefiner) {
                /** @var iDefiner $app */
                return $app->getDefinition($path, $default_value, $check_type);
            }
        }

        // if execute this, something went wrong
        throw new Exception([
            'App is not a iDefiner implementation, cannot use getDefinition',
            'path'          => $path,
            'default_value' => $default_value,
            'check_type'    => $check_type,
        ]);
    }
}
