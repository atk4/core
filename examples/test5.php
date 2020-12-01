<?php

declare(strict_types=1);

require '../vendor/autoload.php';

function loopToCreateStack($test)
{
    if ($test > 5) {
        $excPrev = new \Exception('Previous Exception');

        $exc = (new \atk4\core\Exception('Test value is too high', 200, $excPrev))
            ->addMoreInfo('test', $test);

        throw $exc->addSolution('Suggested solution test');
    }

    return loopToCreateStack($test + 1);
}

try {
    loopToCreateStack(1);
} catch (\atk4\core\Exception $e) {
    echo $e->getJson();
}
