<?php

namespace TypePHP\NativeCore\Time;

use TypePHP\NativeCore\Application\ApplicationContext;

interface ScheduledTask
{
    public function run(ApplicationContext $context): void;
}
