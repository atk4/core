<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * Typical software design will create the application scope. Most frameworks
 * relies on "static" properties, methods and classes. This does puts some
 * limitations on your implementation (you can't have multiple applications).
 *
 * App Scope will pass the 'app' property into all the object that you're
 * adding, so that you know for sure which application you work with.
 */
trait AppScopeTrait
{
    /**
     * Always points to current application.
     *
     * @var \Atk4\Ui\App
     */
    private $_app;

    /**
     * When using mechanism for ContainerTrait, they inherit name of the
     * parent to generate unique name for a child. In a framework it makes
     * sense if you have a unique identifiers for all the objects because
     * this enables you to use them as session keys, get arguments, etc.
     *
     * Unfortunately if those keys become too long it may be a problem,
     * so ContainerTrait contains a mechanism for auto-shortening the
     * name based around max_name_length. The mechanism does only work
     * if AppScopeTrait is used, $app property is set and has a
     * max_name_length defined.
     *
     * Minimum is 40
     *
     * @var int
     */
    public $max_name_length = 60;

    /**
     * As more names are shortened, the substituted part is being placed into
     * this hash and the value contains the new key. This helps to avoid creating
     * many sequential prefixes for the same character sequence. Those
     * hashes can also be used to re-build the long name of the object, but
     * this functionality is not essential and excluded from traits. You
     * can find it in a test suite.
     *
     * @var array
     */
    public $unique_hashes = [];

    protected function assertInstanceOfApp(object $app): void
    {
        if (!$app instanceof \Atk4\Ui\App) {
            // called from phpunit, allow to use/test this trait without \Atk4\Ui\App class
            if (class_exists(\PHPUnit\Framework\TestCase::class, false)) {
                foreach (debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
                    if (str_starts_with($frame['class'] ?? '', 'Atk4\Core\Tests\\')) {
                        return;
                    }
                }
            }

            throw new Exception('App must be instance of \Atk4\Ui\App');
        }
    }

    public function issetApp(): bool
    {
        return $this->_app !== null;
    }

    /**
     * @return \Atk4\Ui\App
     */
    public function getApp()
    {
        $this->assertInstanceOfApp($this->_app);

        return $this->_app;
    }

    /**
     * @param \Atk4\Ui\App $app
     *
     * @return static
     */
    public function setApp(object $app)
    {
        $this->assertInstanceOfApp($app);
        if ($this->issetApp() && $this->getApp() !== $app) {
            if ($this->getApp()->catch_exceptions || $this->getApp()->always_run) { // allow to replace App created by AbstractView::initDefaultApp() - TODO fix
                throw new Exception('App cannot be replaced');
            }
        }

        $this->_app = $app;

        return $this;
    }
}
