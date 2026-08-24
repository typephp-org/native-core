<?php

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Console\ConsoleHost;
use TypePHP\NativeCore\Host\Console\ConsoleProgram;
use TypePHP\NativeCore\Time\BlockingScheduler;
use TypePHP\NativeCore\Time\ScheduledTask;
use TypePHP\NativeCore\Time\SystemSleeper;

final class StabilityTask implements ScheduledTask
{
    private int $startedAt;
    private int $durationMs;
    private int $ticks = 0;
    private int $baseline;
    private int $peak;

    public function __construct(int $durationSeconds)
    {
        $this->startedAt = (int) floor(microtime(true) * 1000);
        $this->durationMs = $durationSeconds * 1000;
        $this->baseline = memory_get_usage(true);
        $this->peak = $this->baseline;
    }

    public function run(ApplicationContext $context): void
    {
        $this->ticks++;
        $current = memory_get_usage(true);
        if ($current > $this->peak) {
            $this->peak = $current;
        }
        if ($context->clock()->nowMilliseconds() - $this->startedAt >= $this->durationMs) {
            $context->cancellation()->requestCancellation();
            echo 'STABILITY ticks=' . $this->ticks
                . ' baseline_bytes=' . $this->baseline
                . ' peak_bytes=' . $this->peak
                . ' final_bytes=' . $current . "\n";
        }
    }
}

final class StabilityProgram implements ConsoleProgram
{
    private int $seconds;
    public function __construct(int $seconds) { $this->seconds = $seconds; }
    public function execute(ApplicationContext $context): int
    {
        (new BlockingScheduler(new SystemSleeper()))
            ->run(new StabilityTask($this->seconds), $context, 10, 0);
        return 0;
    }
}

function main(): void
{
    global $argv;
    $seconds = 1;
    if (count($argv) > 1) {
        $seconds = max(1, (int) $argv[1]);
    }
    $application = NativeApplication::configure()->build();
    $application->run(new ConsoleHost(new StabilityProgram($seconds)));
}
