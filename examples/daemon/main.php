<?php

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Daemon\DaemonHost;
use TypePHP\NativeCore\Host\Daemon\DaemonWorker;
use TypePHP\NativeCore\Logging\JsonLineLogger;
use TypePHP\NativeCore\Signals\NoopSignalSource;
use TypePHP\NativeCore\Time\SystemSleeper;

final class ExampleDaemonWorker implements DaemonWorker
{
    private int $ticks = 0;

    public function tick(ApplicationContext $context): void
    {
        $this->ticks++;
        $context->logger()->log('info', 'daemon tick', ['iteration' => $this->ticks]);
        if ($this->ticks >= 3) {
            $context->cancellation()->requestCancellation();
        }
    }
}

function main(): void
{
    $application = NativeApplication::configure()->withLogger(new JsonLineLogger())->build();
    $host = new DaemonHost(new ExampleDaemonWorker(), new NoopSignalSource(), new SystemSleeper(), 10);
    $application->run($host);
}
