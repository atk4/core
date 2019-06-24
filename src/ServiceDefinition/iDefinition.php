<?php
declare(strict_types=1);

namespace atk4\core\ServiceDefinition;

interface iDefinition
{
    public function process(iDefiner $iDefiner);
}
