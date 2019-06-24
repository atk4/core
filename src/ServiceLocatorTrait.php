<?php

declare(strict_types=1);

namespace atk4\core;

use atk4\core\ServiceDefinition\Factory;
use atk4\core\ServiceDefinition\iDefiner;
use atk4\core\ServiceDefinition\Instance;

trait ServiceLocatorTrait
{
    use ConfigTrait;

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
    public function getService(string $fqcn, $default_object = null)
    {
        if (isset($this->app) && $this->app !== $this && ($app = $this->app) instanceof iDefiner) {
            /* @var iDefiner $app */
            return $app->getService($fqcn, $default_object);
        }

        if (!($this instanceof iDefiner)) {
            throw new Exception([
                'You need to implement iDefiner in this object or in $this->app',
                'object' => $this,
            ]);
        }

        $this->checkTypeExists($fqcn);

        $element = $this->getConfig($fqcn, $default_object);

        // normalize getConfig return ( if not found getConfig return false not null)
        // if no $element => set to default
        if (false === $element || null === $element) {
            $element = $default_object;
        }

        // if is a Factory
        // call Factory->process
        // which create a new object
        // and return it
        // further calls => create a new object
        if ($element instanceof Factory) {
            $element = $element->process($this);
        }

        // if is a Instance
        // call Instance->process
        // which create the new object
        // set in config
        // and return it
        // further calls => get the already created object from config elements
        if ($element instanceof Instance) {
            $element = $element->process($this);
            $this->setConfig($fqcn, $element);
        }

        $this->checkTypeElement($fqcn, $element);

        return $element;
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
        if (!is_a($element, $Type) || null === $element) {
            throw new Exception([
                'Type of returned instance is not of type : '.$Type,
                'Type'    => $Type,
                'Element' => $element,
            ]);
        }
    }
}
