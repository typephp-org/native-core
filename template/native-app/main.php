<?php

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Console\ConsoleHost;
use TypePHP\NativeCore\Host\Console\ConsoleProgram;
use TypePHP\NativeCore\Logging\JsonLineLogger;

final class Program implements ConsoleProgram
{
    public function execute(ApplicationContext $context): int
    {
        $context->logger()->log('info', 'native app started', []);
        return 0;
    }
}

function main(): void
{
    $application = NativeApplication::configure()->withLogger(new JsonLineLogger())->build();
    $application->run(new ConsoleHost(new Program()));
}
