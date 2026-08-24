<?php

namespace TypePHP\NativeCore\Time;

use TypePHP\NativeCore\Application\ApplicationContext;

final class BlockingScheduler
{
    private Sleeper $sleeper;

    public function __construct(Sleeper $sleeper)
    {
        $this->sleeper = $sleeper;
    }

    public function run(ScheduledTask $task, ApplicationContext $context, int $intervalMs, int $maxIterations): int
    {
        $iterations = 0;
        while (!$context->cancellation()->isCancellationRequested()) {
            $task->run($context);
            $iterations++;
            if ($maxIterations > 0 && $iterations >= $maxIterations) {
                break;
            }
            if ($context->cancellation()->isCancellationRequested()) {
                break;
            }
            $this->sleeper->sleepMilliseconds($intervalMs);
        }
        return $iterations;
    }
}
