<?php

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Console\ConsoleHost;
use TypePHP\NativeCore\Host\Console\ConsoleProgram;
use TypePHP\NativeCore\Logging\JsonLineLogger;
use TypePHP\NativeCore\Time\BlockingScheduler;
use TypePHP\NativeCore\Time\ScheduledTask;
use TypePHP\NativeCore\Time\SystemSleeper;

final class ExampleJob implements ScheduledTask
{
    private int $runs = 0;

    public function run(ApplicationContext $context): void
    {
        $this->runs++;
        $context->logger()->log('info', 'worker tick', ['iteration' => $this->runs]);
        if ($this->runs >= 3) {
            $context->cancellation()->requestCancellation();
        }
    }
}

final class WorkerProgram implements ConsoleProgram
{
    public function execute(ApplicationContext $context): int
    {
        $scheduler = new BlockingScheduler(new SystemSleeper());
        $scheduler->run(new ExampleJob(), $context, 10, 0);
        return 0;
    }
}

function main(): void
{
    $app = NativeApplication::configure()->withLogger(new JsonLineLogger())->build();
    $app->run(new ConsoleHost(new WorkerProgram()));
}
