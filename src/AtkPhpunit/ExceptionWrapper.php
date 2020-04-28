<?php

declare(strict_types=1);

namespace atk4\core\AtkPhpunit;

require_once __DIR__ . '/phpunit_polyfill.php';

/**
 * Generic PHPUnit exception wrapper for ATK4 repos.
 */
class ExceptionWrapper extends \PHPUnit\Framework\Exception
{
    /** @var \Throwable Previous exception */
    public $previous;

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        $this->previous = $previous;
        parent::__construct($message, $code, $previous);
    }
}
