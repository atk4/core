# 1.3

This release refactors FactoryTrait in a way that may not be backwards compatible, so upgrade with care.

 - Implemented a consistent [Seed Support](http://agile-core.readthedocs.io/en/develop/factory.html#seed)
 - Changed [how "namespaces" prefixing work](http://agile-core.readthedocs.io/en/develop/factory.html#namespace)
 - Refactored [DebugTrait, now PSR-3 friedly](http://agile-core.readthedocs.io/en/develop/debug.html)
 - Added [documentation index](http://agile-core.readthedocs.io/en/develop/index.html)
 - Migrated to CodeCov and achieved 100% test coverage
 - Added brief [documentation for debugTraceChange](http://agile-core.readthedocs.io/en/develop/debug.html#debugtracechange)

### Minor releases (in reverse order)

#### 1.3.1

- Added implementation for [seed mering](http://agile-core.readthedocs.io/en/develop/factory.html#seed-merging)
- Fix the way how setDefaults treats numeric arguments #63

#### 1.3.2

- Fixed bug that would prevent random Forms from submitting proprely #64

#### 1.3.2

- Added prefix to seed, was causing atk4/ui/Form to randomly fail, when name is shortened

#### 1.3.3

- Removed php-coveralls from runtime dependency

#### 1.3.4

- Removed special treatment for array property merging #67

#### 1.3.5

- Feature/php72 no longer has stderr [\#73](https://github.com/atk4/core/pull/73) ([romaninsh](https://github.com/romaninsh))
- we require LoggerInterface always [\#72](https://github.com/atk4/core/pull/72) ([DarkSide666](https://github.com/DarkSide666))
- spellcheck and wrapping :\) [\#71](https://github.com/atk4/core/pull/71) ([DarkSide666](https://github.com/DarkSide666))
- use $app-\>logger and send errors there rather than app itself [\#70](https://github.com/atk4/core/pull/70) ([romaninsh](https://github.com/romaninsh))
- Feature/add html console exception output [\#69](https://github.com/atk4/core/pull/69) ([romaninsh](https://github.com/romaninsh))

#### 1.3.6

- Fixed: injecting "null" argument into constructor fails [\#74](https://github.com/atk4/core/issues/74)

#### 1.3.6

**Merged pull requests:**

- implement PSR3 support in DebugTrait [\#77](https://github.com/atk4/core/pull/77) ([romaninsh](https://github.com/romaninsh))

#### 1.3.7

**Closed issues:**

- Injecting "null" argument into constructor fails [\#74](https://github.com/atk4/core/issues/74)

**Merged pull requests:**

- add more tests [\#76](https://github.com/atk4/core/pull/76) ([DarkSide666](https://github.com/DarkSide666))
- numeric key seed always should go trough [\#75](https://github.com/atk4/core/pull/75) ([DarkSide666](https://github.com/DarkSide666))

#### 1.3.8

**Merged pull requests:**

- add comments [\#78](https://github.com/atk4/core/pull/78) ([DarkSide666](https://github.com/DarkSide666))

## 1.2.2

Added DIContainerTrait and documented

## 1.2.1

Remove exception for undefined property defaults, see #46

## 1.2.0

This release becomes more strict about undefined properties and what you pass as a second argument
to the add methods. If you upgrade you might get some errors but those are easy to fix

 - $exception->setMessage() is now possible. Can be useful to alter error message when we re-throwing it or for localization.
 - factory($object, $defaults) now works consistently with factory($string, $defaults)
 - adding some object into multiple containers won't execute init() again.
 - added release script

## 1.1.11

- Display object with the exception dump. #38 

## 1.1.10

 - prevent looping by calling normalizeClass in the API that may also
   implement factory trait and cause a loop

## 1.1.8

 - Added Exception::getHTML(), similar to getColofulException but for
   HTML output
 - Improved handling of unique elements with "desired_name" property
 - Will route 'normalizeClassName' to your application now
 - Various type-hinting improvements and making IDEs happier

## 1.1.8

Fixed minor bug, for objects that have no elements at all

## 1.1.7

 - Added some support files for Psysh (e command) #29
 - Improve debug backtrace by including object references #28
 - implement addMoreInfo for exceptions #27

## 1.1.6

Added some support files for PHPUnit

## 1.1.4

Improved DebugTrait:

 - added debugTraceChange
 - added better integration with app scope
 - added exception::addMoreInfo
 - added documentation for Exception

## 1.1.3

Added draft implementation for Session trait. Minor cleanups.

## 1.1.2

Mostly new tests and improvements in documentation.

## 1.1.1

Focus on adding documentation for existing features, improving comments
and adding examples. 
* src/Object is removed as it wasn't used anywhere.
* added lots of test-scripts

## 1.1.0

If any of your objects use ContainerTrait without TrackableTrait, you may need to update
your code to avoid warnings.

* Significant updates to documentation
* ContainerTrait no longer add 'name' property. [Use it with TrackableTrait or NameTrait](http://agile-core.readthedocs.io/en/develop/container.html?highlight=nametrait#name-trait)
* Coding style improved

## 1.0.3

* Minor cleanups, exception will now show previous exception

## 1.0.2

* Added FactoryTrait
* FactoryTrait has normalizeClassName method

## 1.0.1

* Exception->getColorfulText now will provide console-friendly colorful text
* Hooks can now receive references
* property $elements is now public

## 1.0

* implemented Exception, that supports additional parameters
* implemented ContainerTrait, add one object inside another
* implemented TrackableTrait, being able to find objects within container
* implemented InitializerTrait, objects will call their init() method
* implemented AppScopeTrait, objects will keep $this->app linked with the application
* implemented HookTrait, can add and execute hooks
* implemented DynamicMethod, can register methods dynamically and execute through catch-all

## 0.1

* Initial Release
* Bootstraped Documentation (sphinx-doc)
* Initial test-suite and concept description
