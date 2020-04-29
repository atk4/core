<?php

declare(strict_types=1);

require '../vendor/autoload.php';

function loopToCreateStack($test)
{
    if ($test > 5) {
        $exc_prev = new \Exception('Previous Exception');

        $exc = new atk4\core\Exception([
            'Test value is too high',
            'test' => $test,
        ], 200, $exc_prev);

        throw $exc->addSolution('Suggested solution test');
    }

    return loopToCreateStack($test + 1);
}

try {
    loopToCreateStack(1);
} catch (Exception $e) {
    echo $e->getJSON();
}
