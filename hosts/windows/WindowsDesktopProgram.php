<?php

namespace TypePHP\NativeCore\Host\Windows;

use TypePHP\NativeCore\Application\ApplicationContext;

interface WindowsDesktopProgram
{
    public function run(ApplicationContext $context): int;

    public function requestStop(): void;
}
