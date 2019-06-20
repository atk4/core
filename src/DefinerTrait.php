<?php
declare(strict_types=1);

namespace atk4\core;

use atk4\core\Definition\Factory;
use atk4\core\Definition\iDefiner;
use atk4\core\Definition\iDefinition;
use atk4\core\Definition\Instance;

trait DefinerTrait
{
    use ConfigTrait;

    /**
     * Get Config Element or iDependency Object.
     *
     * @param string     $path
     * @param mixed|null $default_value
     * @param bool       $check_type
     *
     * @throws Exception
     * @return mixed
     */
    public function getDefinition(string $path, $default_value = null, bool $check_type = false)
    {
        $element = $this->getConfig($path, $default_value);

        // if not a iDependency return element
        if (!($element instanceof iDefinition)) {
            return $element;
        }

        if ($check_type) {
            $this->checkTypeExists($path);
        }

        /* @var iDefiner $this */

        // if is a Factory
        // call Factory->process
        // which create a new object
        // and return it
        // further calls => create a new object
        if ($element instanceof Factory) {
            $element = $element->process($this);

            if ($check_type) {
                $this->checkTypeElement($path, $element);
            }

            return $element;
        }

        // if is a Instance
        // call Instance->process
        // which create the new object
        // set in config
        // and return it
        // further calls => get the already created object from config elements
        if ($element instanceof Instance) {
            $element = $element->process($this);

            if ($check_type) {
                $this->checkTypeElement($path, $element);
            }

            $this->setConfig($path, $element);

            return $element;
        }

        throw new Exception('ConfigDI Dependency not found');
    }

    /**
     * Check if FQCN of the element path exists.
     *
     * @param string $Type
     *
     * @throws Exception
     */
    private function checkTypeExists(string $Type): void
    {
        if (!class_exists($Type) && !interface_exists($Type)) {
            throw new Exception([
                'Type for checking definition element not exists : '.$Type,
                'Type' => $Type,
            ]);
        }
    }

    /**
     * Check consistency for return type from iDefiner implementation.
     *
     * @param string $Type
     * @param mixed  $element
     *
     * @throws Exception
     */
    private function checkTypeElement(string $Type, $element): void
    {
        if (!($element instanceof $Type)) {
            throw new Exception([
                'Type of returned instance is not of type : '.$Type,
                'Type'    => $Type,
                'Element' => $element,
            ]);
        }
    }
}
