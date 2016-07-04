# Agile Core - Trait collection for PHP frameworks

**Agile Core is a collection of PHP Traits for designing object-oriented frameworks**

[![Gitter](https://img.shields.io/gitter/room/atk4/data.svg?maxAge=2592000)](https://gitter.im/atk4/dataset?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Documentation Status](https://readthedocs.org/projects/agile-core/badge/?version=latest)](http://agile-core.readthedocs.io/en/latest/?badge=latest)
[![License](https://poser.pugx.org/atk4/core/license)](https://packagist.org/packages/atk4/core)
[![GitHub release](https://img.shields.io/github/release/atk4/core.svg?maxAge=2592000)](https://packagist.org/packages/atk4/core)
[![Build Status](https://travis-ci.org/atk4/core.png?branch=develop)](https://travis-ci.org/atk4/core)
[![Code Climate](https://codeclimate.com/github/atk4/core/badges/gpa.svg)](https://codeclimate.com/github/atk4/core)
[![Test Coverage](https://codeclimate.com/github/atk4/core/badges/coverage.svg)](https://codeclimate.com/github/atk4/core/coverage)
[![Issue Count](https://codeclimate.com/github/atk4/core/badges/issue_count.svg)](https://codeclimate.com/github/atk4/core)


## Introducing the concept

Implement your base classes of your framework by using some of the traits. Make your framework more lightweigth and elegant. Multiple parts of your framework will be able to interact easily.

 - Run-time tree (containers, add() method)
 - Initializers (calling init() method)
 - Factory (specifying class name by string)
 - Dynamic Methods (addMethod())
 - Hooks (addHook())
 - Modelable (setModel())
 - Quick Exception (context-aware exception() method)
 - App Scope ($object->app)
 - Session (memorize() and recall())
 - Debug (log(), warn(), debug())
 
## Documentation and Sample Code

See http://agile-core.readthedocs.io/

## Current Status

Stable

## Roadmap

```
1.1   Implement Debug
1.2   Implement QuickException
1.3   Implement Sessions
1.4   Implement Renderable and Template
```

## Past Updates

* 04 Jul: Implemented FactoryTrait
* 21 May: Released 1.0: Implemented ContainerTrait, Trackable, Initializer, AppScope, Hooks, DynamicMethod
* 11 May: Released 0.1
* 11 May: Finished basic docs
* 27 Apr: Initial Commit

