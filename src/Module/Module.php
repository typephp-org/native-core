<?php

namespace TypePHP\NativeCore\Module;

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Services\ServiceRegistry;

interface Module
{
    public function apiVersion(): int;

    public function register(ServiceRegistry $services): void;

    public function start(ApplicationContext $context): void;

    public function stop(ApplicationContext $context): void;
}
