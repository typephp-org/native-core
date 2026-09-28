<?php

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Worker\Worker;
use TypePHP\NativeCore\Host\Worker\WorkerHost;
use TypePHP\NativeCore\Host\Worker\WorkResult;
use TypePHP\NativeCore\Logging\JsonLineLogger;

final class ExampleDaemonWorker implements Worker
{
    private int $ticks = 0;

    public function handle(ApplicationContext $context): WorkResult
    {
        $this->ticks++;
        $context->logger()->log('info', 'daemon tick', ['iteration' => $this->ticks]);
        if ($this->ticks >= 3) {
            return WorkResult::stop();
        }
        return WorkResult::continueAfter(10);
    }
}

function main(): void
{
    $application = NativeApplication::configure()->withLogger(new JsonLineLogger())->build();
    $application->run(WorkerHost::defaults(new ExampleDaemonWorker()));
}
