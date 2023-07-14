:::{php:namespace} Atk4\Core
:::

# Config Trait

:::{php:trait} ConfigTrait
:::

Agile Core implements support for read configuration files of different formats

## Introduction

This trait can be added to any object to load a configuration.
Configuration files can be of 4 types: php, php-inline, json, yaml.

Loading can be done in this way:

```
$object = new Object();
$object->readConfig('config.php', 'php');
```

After loading, configuration elements can be retrieved in this way:

```
$object->getConfig('element_key', 'if not defined use this as default');
```

if you need an element that is declared inside an array you can use a special syntax:

```
$object->getConfig('level1_array/level2_array/element_key', 'if not defined use this as default');
```

Element in config can be defined even manually:

```
$object->setConfig('element_key', $element);
```

## Supported Formats

### php

Configuration is defined as a return statement

```
return [
    'var A' => new UserClass(),
    'var B' => 2,
    'var C' => [
        '2nd-level' => 'var D',
    ],
];
```

### JSON

Configuration is defined as json

### YAML

Configuration is defined as yaml

## Methods

:::{php:method} readConfig($files = ['config.php'], $format = 'php')
Read config file or files and store it in $config property
:::

:::{php:method} setConfig($paths = [], $value = null)
Manually set configuration option
:::

:::{php:method} getConfig($path, $defaultValue = null)
Get configuration element
:::
