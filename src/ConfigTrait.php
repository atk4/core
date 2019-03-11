<?php

namespace atk4\core;

/**
 * This trait makes it possible for you to read config files and various configurations
 * use:
 * 1. use Trait in your APP Class
 *    use \atk4\core\ConfigTrait;
 * 2. create config-default.php and/or config.php file and add config values like
 *    $config['key'] = 'value';
 * 3. call $this->readConfig();
 *    before using config.
 */
trait ConfigTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_configTrait = true;

    /**
     * This property stores config values. Use getConfig() method to access its values.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Read config file or files and store it in $config property.
     *
     * Supported formats:
     *  php         - PHP file with $config['foo'] = 'bar' structure
     *  php-inline  - PHP file with return ['foo' => 'bar'] structure
     *  json        - JSON file with {'foo':'bar'} structure
     *  yaml        - YAML file with yaml structure
     *
     * @param string|array $files  One or more filenames
     * @param string       $format Optional format for config files
     *
     * @return $this
     */
    public function readConfig($files = ['config.php'], $format = 'php')
    {
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (!is_readable($file)) {
                throw new Exception(['Can not read config file', 'file' => $file, 'format' => $format]);
            }

            $tempConfig = [];

            switch (strtolower($format)) {
                case 'php':
                    $config = null;
                    require $file; // fills $config variable
                    $tempConfig = $config;
                    break;

                case 'php-inline':
                    $tempConfig = require $file;
                    break;

                case 'json':
                    $tempConfig = json_decode(file_get_contents($file), true);
                    break;

                case 'yaml':
                    // @codeCoverageIgnoreStart
                    if (!class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                        throw new Exception(['You need Symfony\Yaml repository if you want to parse YAML files']);
                    }
                    $tempConfig = \Symfony\Component\Yaml\Yaml::parseFile($file);
                    // @codeCoverageIgnoreEnd
                    break;
            }

            $this->config = array_merge_recursive($this->config, $tempConfig);
        }

        return $this;
    }

    /**
     * Manually set configuration option.
     *
     * @param string|array $paths Path to configuration element to set or array of [path=>value]
     * @param mixed        $value Value to set
     *
     * @return $this
     */
    public function setConfig($paths = [], $value = null)
    {
        if (!is_array($paths)) {
            $paths = [$paths => $value];
        }

        foreach ($paths as $path=>$value) {
            $pos = &$this->_lookupConfigElement($path, true);

            if (is_array($pos) && !empty($pos) && is_array($value)) {
                // special treatment for arrays - merge them
                $pos = array_merge($pos, $value);
            } else {
                // otherwise just assign value
                $pos = $value;
            }
        }

        return $this;
    }

    /**
     * Get configuration element.
     *
     * @param string $path          Path to configuration element.
     * @param mixed  $default_value Default value returned if element don't exist
     *
     * @return mixed
     */
    public function getConfig($path, $default_value = null)
    {
        $pos = &$this->_lookupConfigElement($path, false);

        // path element don't exist - return default value
        if ($pos === false) {
            return $default_value;
        }

        return $pos;
    }

    /**
     * Internal method to lookup config element by given path.
     *
     * @param string $path            Path to navigate to
     * @param bool   $create_elements Should we create elements it they don't exist
     *
     * @return &pos|false Pointer to element in $this->config or false is element don't exist and $create_elements===false
     *                    Returns false if element don't exist and $create_elements===false
     */
    protected function &_lookupConfigElement($path, $create_elements = false)
    {
        $path = explode('/', $path);
        $pos = &$this->config;
        foreach ($path as $el) {
            // create empty element if it doesn't exist
            if (!array_key_exists($el, $pos) && $create_elements) {
                $pos[$el] = [];
            }
            // if it still doesn't exist, then just return false (no error)
            if (!array_key_exists($el, $pos) && !$create_elements) {
                // trick to return false because we need reference here
                $false = false;

                return $false;
            }

            $pos = &$pos[$el];
        }

        return $pos;
    }
}
