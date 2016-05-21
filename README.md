# Agile Core - Trait collection for PHP frameworks

**Agile Core is a collection of PHP Traits for designing object-oriented frameworks**

[![Join the chat at https://gitter.im/atk4/data](https://badges.gitter.im/atk4/data.svg)](https://gitter.im/atk4/data?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
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

Stable (BETA1)

## Roadmap

```
1.1   Implement Debug
1.2   Implement Factory
1.3   Implement QuickException
1.4   Implement Sessions
1.5   Implement Renderable and Template
```

## Past Updates

* 21 May: Released 1.0: Implemented ContainerTrait, Trackable, Initializer, AppScope, Hooks, DynamicMethod
* 11 May: Released 0.1
* 11 May: Finished basic docs
* 27 Apr: Initial Commit

