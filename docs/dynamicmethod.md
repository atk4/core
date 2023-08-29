:::{php:namespace} Atk4\Core
:::

# Dynamic Method Trait

:::{php:trait} DynamicMethodTrait
:::

## Introduction

Adds ability to add methods into objects dynamically. That's like a "trait"
feature of a PHP, but implemented in run-time:

```
$object->addMethod('test', function ($o, $args) {
    echo 'hello, ' . $args[0];
});
$object->test('world');
```

## Dynamic Method Arguments

When calling dynamic method first argument which is passed to the method will
be object itself. Dynamic method will also receive all arguments which are
given when you call this dynamic method:

```
$m->addMethod('sum', function ($m, $a, $b) {
    return $a + $b;
});
echo $m->sum(3, 5); // 8
```

## Properties

None

## Methods

:::{php:method} tryCall($method, $arguments)
Tries to call dynamic method, but doesn't throw exception if it is not
possible.
:::

:::{php:method} addMethod($name, $closure)
Add new method for this object.
See examples above.
:::

:::{php:method} hasMethod($name)
Returns true if object has specified method (either native or dynamic).
Returns true also if specified methods is defined globally.
:::

:::{php:method} removeMethod($name)
Remove dynamically registered method.
:::
