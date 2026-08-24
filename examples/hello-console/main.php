<?php

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Config\ArrayConfig;
use TypePHP\NativeCore\Config\Environment;
use TypePHP\NativeCore\Host\Console\ConsoleHost;
use TypePHP\NativeCore\Host\Console\ConsoleProgram;
use TypePHP\NativeCore\Logging\JsonLineLogger;

final class HelloConsoleProgram implements ConsoleProgram
{
    public function execute(ApplicationContext $context): int
    {
        $context->logger()->log('info', 'hello from native core', [
            'environment' => $context->environment()->name(),
            'runtime' => $context->config()->string('runtime', 'unknown'),
        ]);
        return 0;
    }
}

function main(): void
{
    $app = NativeApplication::configure()
        ->withConfig(new ArrayConfig(['runtime' => 'dual']))
        ->withEnvironment(new Environment('development', true))
        ->withLogger(new JsonLineLogger())
        ->build();

    $app->run(new ConsoleHost(new HelloConsoleProgram()));
}
