<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * This trait makes it possible for you to read config files and various configurations
 * use:
 * 1. use Trait in your APP Class
 *    use \Atk4\Core\ConfigTrait;
 * 2. create config-default.php and/or config.php file and add config values like
 *    return ['key' => 'value'];
 * 3. call $this->readConfig();
 *    before using config.
 */
trait ConfigTrait
{
    /** @var array<string, mixed> This property stores config values. Use getConfig() method to access its values. */
    protected array $config = [];

    /**
     * Read config file or files and store it in $config property.
     *
     * Supported formats:
     *  php         - PHP file with return ['foo' => 'bar'] structure
     *  json        - JSON file with { 'foo': 'bar' } structure
     *  yaml        - YAML file with yaml structure
     *
     * @param string|array<int, string> $files  One or more filenames
     * @param string                    $format Optional format for config files
     *
     * @return $this
     */
    public function readConfig($files = ['config.php'], string $format = 'php')
    {
        if (!is_array($files)) {
            $files = [$files];
        }

        $configs = [];
        foreach ($files as $file) {
            if (!is_readable($file)) {
                throw (new Exception('Cannot read config file'))
                    ->addMoreInfo('file', $file)
                    ->addMoreInfo('format', $format);
            }

            $tempConfig = [];

            switch (strtolower($format)) {
                case 'php':
                    $tempConfig = require $file;

                    break;
                case 'json':
                    $tempConfig = json_decode(file_get_contents($file), true);

                    break;
                case 'yaml':
                    $tempConfig = \Symfony\Component\Yaml\Yaml::parseFile($file);

                    break;
                default:
                    throw (new Exception('Unknown Format. Allowed formats: php, json, yml'))
                        ->addMoreInfo('file', $file)
                        ->addMoreInfo('format', $format);
            }

            if (!is_array($tempConfig)) {
                throw (new Exception('File was read but has a bad format'))
                    ->addMoreInfo('file', $file)
                    ->addMoreInfo('format', $format);
            }

            $configs[] = $tempConfig;
        }

        $this->config = array_replace_recursive($this->config, ...$configs);

        return $this;
    }

    /**
     * Manually set configuration option.
     *
     * @param string|array<string, mixed>       $paths Path to configuration element to set or array of [path => value]
     * @param ($paths is array ? never : mixed) $value Value to set
     *
     * @return $this
     */
    public function setConfig($paths = [], $value = null)
    {
        if (!is_array($paths)) {
            $paths = [$paths => $value];
        }
        unset($value);

        foreach ($paths as $path => $value) {
            $pos = &$this->_lookupConfigElement($path, true);

            if (is_array($pos) && count($pos) > 0 && is_array($value)) {
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
     * @param string $path         path to configuration element
     * @param mixed  $defaultValue Default value returned if element don't exist
     *
     * @return mixed
     */
    public function getConfig(string $path, $defaultValue = null)
    {
        $pos = &$this->_lookupConfigElement($path, false);

        // path element don't exist - return default value
        if ($pos === false) {
            return $defaultValue;
        }

        return $pos;
    }

    /**
     * Internal method to lookup config element by given path.
     *
     * @param string $path           Path to navigate to
     * @param bool   $createElements Should we create elements it they don't exist
     *
     * @return mixed|false Pointer to element in $this->config or false is element don't exist and $createElements === false
     */
    private function &_lookupConfigElement(string $path, bool $createElements = false)
    {
        $path = explode('/', $path);
        $pos = &$this->config;
        foreach ($path as $el) {
            if (!is_array($pos) || !array_key_exists($el, $pos)) {
                if (!is_array($pos) || !$createElements) {
                    $res = false;

                    return $res;
                }

                $pos[$el] = [];
            }

            $pos = &$pos[$el];
        }

        return $pos;
    }
}
