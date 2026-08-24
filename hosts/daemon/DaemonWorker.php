<?php

namespace TypePHP\NativeCore\Host\Daemon;

use TypePHP\NativeCore\Application\ApplicationContext;

interface DaemonWorker
{
    public function tick(ApplicationContext $context): void;
}
