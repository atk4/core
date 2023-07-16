:::{php:namespace} Atk4\Core
:::

# Initializer Trait

:::{php:trait} InitializerTrait
:::

## Introduction

With our traits objects now become linked with the "owner" and the "app".
Initializer trait allows you to define a method that would be called after
object is linked up into the environment.

Declare a object class in your framework:

```
class FormField
{
    use AppScopeTrait;
    use InitializerTrait;
    use NameTrait;
    use TrackableTrait;
}

class FormField_Input extends FormField
{
    public $value = null;

    protected function init(): void
    {
        parent::init();

        if ($_POST[$this->name) {
            $this->value = $_POST[$this->name];
        }
    }

    public function render()
    {
        return $this->getApp()->getTag('input/', ['name' => $this->name, 'value' => $value]);
    }
}
```

## Methods

:::{php:method} init()
A blank init method that should be called. This will detect the problems
when init() methods of some of your base classes has not been executed and
prevents from some serious mistakes.
:::

If you wish to use traits class and extend it, you can use this in your base
class:

```
class FormField
{
    use AppScopeTrait;
    use InitializerTrait {
        init as _init
    }
    use TrackableTrait;
    use NameTrait;

    public $value = null;

    protected function init(): void
    {
        $this->_init(); // call init of InitializerTrait

        if ($_POST[$this->name) {
            $this->value = $_POST[$this->name];
        }
    }
}
```
