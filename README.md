# Agile Core - Trait collection for PHP frameworks

[![Join the chat at https://gitter.im/atk4/core](https://badges.gitter.im/atk4/core.svg)](https://gitter.im/atk4/core?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

**Agile Core is a collection of PHP Traits for designing object-oriented frameworks**

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

See http://atk4-core.readthedocs.io/

## Current Status

Initial development (pre-alpha)

## Roadmap

```
0.2   Implement ConainerTrait, Trackable, Initializer and AppScope
0.3   Implement Debug
0.4   Implement Factory
0.5   Implement Hooks and Dynamic methods
0.6   Implement QuickException
1.0   First Stable Release.
1.1   Implement Sessions
1.2   Implement Renderable and Template
```

## Past Updates

* 11 May: Released 0.1
* 11 May: Finished basic docs
* 27 Apr: Initial Commit

