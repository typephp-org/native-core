<?php

namespace TypePHP\NativeCore\Host\Console;

use TypePHP\NativeCore\Application\ApplicationContext;

interface ConsoleProgram
{
    public function execute(ApplicationContext $context): int;
}
