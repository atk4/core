<?php

declare(strict_types=1);

namespace Atk4\Core;

use Atk4\Ui\App;

return false;

return false; // @phpstan-ignore-line
interface TraitUtilAppScopeTrait
{
    public function assertInstanceOfApp(object $app): void;

    public function issetApp(): bool;

    /**
     * @return App
     */
    public function getApp();

    /**
     * @param App $app
     *
     * @return static
     */
    public function setApp(object $app);
}
