<?php
declare(strict_types=1);

namespace atk4\core\Definition;

interface iDefinition
{
    public function process(iDefiner $iDefiner);
}
