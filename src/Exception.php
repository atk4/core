<?php

declare(strict_types=1);

namespace Atk4\Core;

use Atk4\Core\Translator\ITranslatorAdapter;

/**
 * Base exception of all Agile Toolkit exceptions.
 */
class Exception extends \Exception
{
    use WarnDynamicPropertyTrait;

    /** @var string */
    protected $customExceptionTitle = 'Critical Error';

    /** @var array<string, mixed> */
    private $params = [];

    /** @var array<int, string> */
    private $solutions = [];

    /** @var ITranslatorAdapter */
    private $translator;

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        // save trace but skip constructors of this exception
        $trace = debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT);
        for ($i = 0; $i < count($trace); ++$i) {
            $frame = $trace[$i];
            if (isset($frame['object']) && $frame['object'] === $this && $frame['function'] === '__construct') {
                array_shift($trace);
            }
        }
        $traceReflectionProperty = new \ReflectionProperty(parent::class, 'trace');
        $traceReflectionProperty->setAccessible(true);
        $traceReflectionProperty->setValue($this, $trace);
    }

    /**
     * Change message (subject) of a current exception. Primary use is
     * for localization purposes.
     *
     * @return $this
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Return exception message using color sequences.
     *
     * <exception name>: <string>
     * <info>
     *
     * trace
     *
     * --
     * <triggered by>
     */
    public function getColorfulText(): string
    {
        return (string) new ExceptionRenderer\Console($this, $this->translator);
    }

    /**
     * Return exception message using HTML block and Fomantic-UI formatting. It's your job
     * to put it inside boilerplate HTML and output, e.g:.
     *
     * $app = new \Atk4\Ui\App();
     * $app->initLayout([\Atk4\Ui\Layout\Centered::class]);
     * $app->layout->template->dangerouslySetHtml('Content', $e->getHtml());
     * $app->run();
     * $app->callBeforeExit();
     */
    public function getHtml(): string
    {
        return (string) new ExceptionRenderer\Html($this, $this->translator);
    }

    /**
     * Return exception in JSON Format.
     */
    public function getJson(): string
    {
        return (string) new ExceptionRenderer\Json($this, $this->translator);
    }

    /**
     * Follow the getter-style of PHP Exception.
     *
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function addMoreInfo(string $param, $value): self
    {
        $this->params[$param] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function addSolution(string $solution): self
    {
        $this->solutions[] = $solution;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getSolutions(): array
    {
        return $this->solutions;
    }

    public function getCustomExceptionTitle(): string
    {
        return $this->customExceptionTitle;
    }

    /**
     * Set custom Translator adapter.
     *
     * @return $this
     */
    public function setTranslatorAdapter(ITranslatorAdapter $translator = null): self
    {
        $this->translator = $translator;

        return $this;
    }
}
