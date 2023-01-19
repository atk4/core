<?php

declare(strict_types=1);

namespace Atk4\Core;

use Atk4\Ui\App;

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
    /** @var QuietObjectWrapper<App>|null */
    private ?QuietObjectWrapper $_app = null;

    /**
     * When using mechanism for ContainerTrait, they inherit name of the
     * parent to generate unique name for a child. In a framework it makes
     * sense if you have a unique identifiers for all the objects because
     * this enables you to use them as session keys, get arguments, etc.
     *
     * Unfortunately if those keys become too long it may be a problem,
     * so ContainerTrait contains a mechanism for auto-shortening the
     * name based around maxNameLength. The mechanism does only work
     * if AppScopeTrait is used, $app property is set and has a
     * maxNameLength defined.
     *
     * @var int<40, max>
     */
    public int $maxNameLength = 60;

    /**
     * As more names are shortened, the substituted part is being placed into
     * this hash and the value contains the new key. This helps to avoid creating
     * many sequential prefixes for the same character sequence. Those
     * hashes can also be used to re-build the long name of the object, but
     * this functionality is not essential and excluded from traits. You
     * can find it in a test suite.
     *
     * @var array<string, string>
     */
    public array $uniqueNameHashes = [];

    protected function assertInstanceOfApp(object $app): void
    {
        if (!$app instanceof App) {
            // called from phpunit, allow to test this trait without Atk4\Ui\App class
            if (class_exists(\PHPUnit\Framework\TestCase::class, false)) {
                foreach (debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
                    if (str_starts_with($frame['class'] ?? '', 'Atk4\Core\Tests\\')) {
                        return;
                    }
                }
            }

            throw new Exception('App must be instance of Atk4\Ui\App');
        }
    }

    public function issetApp(): bool
    {
        return $this->_app !== null;
    }

    /**
     * @return App
     */
    public function getApp()
    {
        $app = $this->_app;
        if ($app === null) {
            throw new Exception('App is not set');
        }

        return $app->get();
    }

    /**
     * @param App $app
     *
     * @return static
     */
    public function setApp(object $app)
    {
        $this->assertInstanceOfApp($app);

        if ($this->issetApp()) {
            throw new Exception('App is already set');
        }

        $this->_app = new QuietObjectWrapper($app);

        return $this;
    }
}
