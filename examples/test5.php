<?php

require '../vendor/autoload.php';

use atk4\core\Exception;

function faulty($test)
{
    if ($test > 5) {
        $exception_prev = new \Exception('Previous Exception');

        $exception = new Exception([
            'Test value is too high',
            'test' => $test,
        ], 200, $exception_prev);
        $exception->addSolution('Suggested solution test');

        throw $exception;
    }

    return faulty($test + 1);
}

try {
    faulty(1);
} catch (Exception $e) {
    echo $e->getJSON();
}
