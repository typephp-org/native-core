<?php

namespace TypePHP\NativeCore\Host\Worker;

use TypePHP\NativeCore\Application\ApplicationContext;

interface Worker
{
    public function handle(ApplicationContext $context): WorkResult;
}
