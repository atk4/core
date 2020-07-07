<?php

declare(strict_types=1);

namespace atk4\core;

class Factory
{
    /** @var Factory */
    private static $_instance;

    protected function __construct()
    {
        // singleton
    }

    final protected static function getInstance(): self
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    protected function printDeprecatedWarningWithTrace(string $msg): void // remove once not used within this class
    {
        static $traceRenderer = null;
        if ($traceRenderer === null) {
            $traceRenderer = new class(new Exception()) extends ExceptionRenderer\Html {
                public function tryRelativizePath(string $path): string
                {
                    try {
                        return $this->makeRelativePath($path);
                    } catch (Exception $e) {
                    }

                    return $path;
                }
            };
        }

        ob_start();
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = preg_replace('~^#0.+?\n~', '', ob_get_clean());
        $trace = preg_replace_callback('~[^\n\[\]<>]+\.php~', function ($matches) use ($traceRenderer) {
            return $traceRenderer->tryRelativizePath($matches[0]);
        }, $trace);
        // echo (new Exception($msg))->getHtml();
        'trigger_error'($msg . (!class_exists(\PHPUnit\Framework\Test::class, false) ? "\n" . $trace : ''), E_USER_DEPRECATED);
    }

    protected function _mergeSeeds($seed, $seed2, ...$more_seeds)
    {
        // recursively merge extra seeds
        if (count($more_seeds) > 0) {
            $seed2 = $this->_mergeSeeds($seed2, ...$more_seeds);
        }

        if ((!is_array($seed) && !is_object($seed) && $seed !== null) || (!is_array($seed2) && !is_object($seed2) && $seed2 !== null)) { // remove/do not accept other seed than object/array type after 2020-dec
            $varName = !is_array($seed) && !is_object($seed) && $seed !== null ? 'seed' : 'seed2';
            $this->printDeprecatedWarningWithTrace(
                'Use of non-array seed ($' . $varName . ' type = ' . gettype(${$varName}) . ') is deprecated and support will be removed shortly.'
            );
        }

        // remove/do not accept seed with 1st argument other than valid class name (or null) after 2020-dec
        $checkSeedClFunc = function ($seed): ?string {
            if (is_object($seed) || $seed === null) {
                return null;
            } elseif (!array_key_exists(0, $seed)) {
                return null; // 'not defined' allow this method to be used to merge seeds without class name
            } elseif ($seed[0] === null) {
                return null;
            } elseif (!is_string($seed[0])) {
                return 'invalid type (' . (is_object($seed[0]) ? get_class($seed[0]) . ' (class wrapped in an array?)' : gettype($seed[0])) . ')';
            } elseif (class_exists($seed[0])) {
                return null;
            }

            // do not emit warnings for core tests:
            // - some tests already tests for exception
            // - we may later want to use this function for "mergeDefaults" (like _factory() below does)
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $cl) {
                if (strpos($cl['class'] ?? '', 'atk4\core\tests\\') === 0) {
                    return null;
                }
            }

            return 'non-existing/non-autoloadable (' . $seed[0] . ')';
        };
        if ($checkSeedClFunc($seed) !== null || $checkSeedClFunc($seed2) !== null) {
            $varName = $checkSeedClFunc($seed) ? 'seed' : 'seed2';
            $this->printDeprecatedWarningWithTrace(
                'Use of invalid/deprecated $' . $varName . ' class name (' . $checkSeedClFunc(${$varName}) . '). Support will be removed shortly.'
            );
        }

        if (is_object($seed) || is_object($seed2)) {
            if (is_object($seed)) {
                $passively = true; // set defaults but don't override existing properties
            } else {
                $passively = false;
                [$seed, $seed2] = [$seed2, $seed]; // swap seeds
            }

            if (is_array($seed2)) {
                $arguments = array_filter($seed2, 'is_numeric', ARRAY_FILTER_USE_KEY); // with numeric keys
                $injection = array_diff_key($seed2, $arguments); // with string keys
                unset($seed2);
                unset($arguments[0]); // the first argument specifies a class name

                if (count($arguments) > 0) {
                    throw (new Exception('Constructor arguments can not be injected into existing object'))
                        ->addMoreInfo('object', $seed)
                        ->addMoreInfo('arguments', $arguments);
                }

                if (count($injection) > 0) {
                    if (isset($seed->_DiContainerTrait)) {
                        $seed->setDefaults($injection, $passively);
                    } else {
                        throw (new Exception('Property injection is possible only to objects that use \atk4\core\DiContainerTrait trait'))
                            ->addMoreInfo('object', $seed)
                            ->addMoreInfo('injection', $injection)
                            ->addMoreInfo('passively', $passively);
                    }
                }
            }

            return $seed;
        }

        if (!is_array($seed)) {
            $seed = [$seed];
        }

        if (!is_array($seed2)) {
            $seed2 = [$seed2];
        }

        // merge seeds but prefer seed over seed2
        // move numerical keys to the beginning and sort them
        $res = [];
        $res2 = [];
        foreach ($seed as $k => $v) {
            if (is_numeric($k)) {
                $res[$k] = $v;
            } elseif ($v !== null) {
                $res2[$k] = $v;
            }
        }
        foreach ($seed2 as $k => $v) {
            if (is_numeric($k)) {
                if (!isset($res[$k])) {
                    $res[$k] = $v;
                }
            } elseif ($v !== null) {
                if (!isset($res2[$k])) {
                    $res2[$k] = $v;
                }
            }
        }
        ksort($res, SORT_NUMERIC);
        $res = $res + $res2;

        return $res;
    }

    protected function _newObject(string $className, array $ctorArgs): object
    {
        return new $className(...$ctorArgs);
    }

    protected function _factory($seed, $defaults = []): object
    {
        if (is_object($defaults)) {
            throw new Exception('Factory $defaults can not be an object');
        }

        if ($defaults === null) { // should be deprecated soon
            $defaults = [];
        }

        if ($seed === null) { // should be deprecated soon
            $seed = [];
        }

        if ((!is_array($seed) && !is_object($seed)) || (!is_array($defaults) && !is_object($defaults))) { // remove/do not accept other seed than object/array type after 2020-dec
            $varName = !is_array($seed) && !is_object($seed) ? 'seed' : 'defaults';
            $this->printDeprecatedWarningWithTrace(
                'Use of non-array seed ($' . $varName . ' type = ' . gettype(${$varName}) . ') is deprecated and support will be removed shortly.'
            );
        }

        if (is_array($defaults)) {
            array_unshift($defaults, null); // insert argument 0
        } else {
            $defaults = [null, $defaults];
        }

        if (is_object($seed)) {
            $defaults = $this->_mergeSeeds([], $defaults);
            $defaults[0] = $seed;
            $seed = $defaults;
        } else {
            if (!is_array($seed)) {
                $seed = [$seed];
            }

            $seed = $this->_mergeSeeds($seed, $defaults);
        }
        unset($defaults);

        $arguments = array_filter($seed, 'is_numeric', ARRAY_FILTER_USE_KEY); // with numeric keys
        $injection = array_diff_key($seed, $arguments); // with string keys
        $object = array_shift($arguments); // first numeric key argument is object

        if (!is_object($object)) {
            if (!is_string($object)) {
                throw (new Exception('Class name is not specified by the seed'))
                    ->addMoreInfo('seed', $seed);
            }

            $object = $this->_newObject($object, $arguments);
        }

        if (count($injection) > 0) {
            $this->_mergeSeeds($injection, $object);
        }

        return $object;
    }

    /**
     * Given two seeds (or more) will merge them, prioritizing the first argument.
     * If object is passed on either of arguments, then it will setDefaults() remaining
     * arguments, respecting their positioning.
     *
     * To learn more about mechanics of factory trait, see documentation
     *
     * @return object|array if at least one seed is an object, will return object
     */
    final public static function mergeSeeds(...$seeds)
    {
        return self::getInstance()->_mergeSeeds(...$seeds);
    }

    /**
     * Given a Seed (see doc) as a first argument, will create object of a corresponding
     * class, call constructor with numerical arguments of a seed and inject key/value
     * arguments.
     *
     * Argument $defaults has the same effect as the seed, but will not contain the class.
     * Class is always determined by seed, except if you pass object into defaults.
     *
     * To learn more about mechanics of factory trait, see documentation
     *
     * @param array $defaults
     */
    final public static function factory($seed, $defaults = []): object
    {
        if (func_num_args() > 2) { // prevent bad usage
            throw new \Error('Too many method arguments');
        }

        return self::getInstance()->_factory($seed, $defaults);
    }
}
