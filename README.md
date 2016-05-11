# Agile Data - Database access abstraction framework.

**Agile Core is a collection of PHP Traits for designing object-oriented frameworks**

[![Build Status](https://travis-ci.org/core/data.png?branch=develop)](https://travis-ci.org/core/data)
[![Code Climate](https://codeclimate.com/github/core/data/badges/gpa.svg)](https://codeclimate.com/github/core/data)
[![Test Coverage](https://codeclimate.com/github/core/data/badges/coverage.svg)](https://codeclimate.com/github/core/data/coverage)
[![Issue Count](https://codeclimate.com/github/core/data/badges/issue_count.svg)](https://codeclimate.com/github/core/data)

The key design concepts and the reason why we created Agile Data are:

 - Agile Data is simple to learn. We have designed our framework with aim to educate developers with
   2+ years of experience on how to properly design application logic.

 - We introduce fresh concepts - DataSet and Action, that result in a more efficient ways to
   interact with non-trivial databases (databases with some query language support).
 
 - Separation of Business Logic and Persistence. We do not allow your database schema to dictate your
   business logic design.

 - Major Databases are supported (SQL and NoSQL) and our framework will automatically use
   features of the database (expressions, sub-queries, multi-row operation) if available.

 - Extensibility. Our core concept is extended through with Joins, SQL Expressions and Sub-Selects,
   Calculated fields, Validation, REST proxies, Caches, etc.

 - Great for UI Frameworks. Agile Data integrates very well with compatible UI layer / widgets.

## Introducing the concept

Implement your base classes of your framework by using some of the traits. Make your framework more lightweigth and elegant. Multiple parts of your framework will be able to interact easily.

Our other design guidelines:

 - write short and easy-to-read, standard-compliant code with high code-climate score.
 - unit-test our own code with minimum of 95% code coverage.
 - add code through pull requests and discuss them before merging.
 - never break APIs in minor releases.
 - support composer but include minimum dependencies.
 - be friendly with all higher-level frameworks.
 - avoid database query latency/overheads, pre-fetching or lazy loading.
 - do not duplicate the code (e.g. in vendor drivers)
 - use MIT License
 
## Sample Code

See http://atk4-core.readthedocs.io/


## Current Status

Initial development

## Roadmap

```
0.1   Implement basic outline, CI, Code Climate and initial docs
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

* 11 May: Finished basic docs
* 27 Apr: Initial Commit

