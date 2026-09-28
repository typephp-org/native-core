<?php

namespace TypePHP\NativeCore\Host\Worker;

use TypePHP\NativeCore\Application\Application;
use TypePHP\NativeCore\Application\Host;
use TypePHP\NativeCore\Signals\NoopSignalSource;
use TypePHP\NativeCore\Signals\SignalSource;
use TypePHP\NativeCore\Time\Sleeper;
use TypePHP\NativeCore\Time\SystemSleeper;

final class WorkerHost implements Host
{
    private Worker $worker;
    private WorkerPolicy $policy;
    private SignalSource $signals;
    private Sleeper $sleeper;
    private WorkerHealth $health;
    private bool $stopRequested;

    public function __construct(
        Worker $worker,
        WorkerPolicy $policy,
        SignalSource $signals,
        Sleeper $sleeper,
        WorkerHealth $health
    ) {
        $this->worker = $worker;
        $this->policy = $policy;
        $this->signals = $signals;
        $this->sleeper = $sleeper;
        $this->health = $health;
        $this->stopRequested = false;
    }

    public static function defaults(Worker $worker): self
    {
        return new self(
            $worker,
            WorkerPolicy::production(),
            new NoopSignalSource(),
            new SystemSleeper(),
            new WorkerHealth()
        );
    }

    public function health(): WorkerHealth
    {
        return $this->health;
    }

    public function run(Application $application): int
    {
        $this->signals->install();
        $this->health->markRunning();

        try {
            while (!$this->shouldStop($application)) {
                $result = $this->execute($application);
                if ($result->action() === WorkResult::STOP) {
                    $this->health->markStopping();
                    $application->requestStop();
                    return 0;
                }

                if ($result->action() === WorkResult::RETRY) {
                    if (!$this->retry($application, $result)) {
                        return 1;
                    }
                    continue;
                }

                $this->health->recordSuccess();
                $delay = $result->delayMilliseconds();
                if ($result->usesPolicyDelay()) {
                    $delay = $this->policy->idleMilliseconds();
                }
                $this->waitIfRunning($application, $delay);
            }

            $this->health->markStopping();
            $application->requestStop();
            return 0;
        } finally {
            if ($this->health->state() !== WorkerHealth::FAILED) {
                $this->health->markStopped();
            }
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
        if ($this->health->state() !== WorkerHealth::FAILED) {
            $this->health->markStopping();
        }
    }

    private function execute(Application $application): WorkResult
    {
        try {
            return $this->worker->handle($application->context());
        } catch (\Throwable $exception) {
            return WorkResult::retry($exception->getMessage());
        }
    }

    private function retry(Application $application, WorkResult $result): bool
    {
        $reason = $result->reason();
        if ($reason === '') {
            $reason = 'worker requested retry';
        }
        $this->health->recordFailure($reason);
        $failures = $this->health->consecutiveFailures();
        $maximum = $this->policy->maximumConsecutiveFailures();

        if ($maximum > 0 && $failures >= $maximum) {
            $this->health->markFailed();
            $application->context()->logger()->log('error', 'worker failure limit reached', [
                'consecutive_failures' => $failures,
                'error' => $reason,
            ]);
            $application->requestStop();
            return false;
        }

        $delay = $result->delayMilliseconds();
        if ($result->usesPolicyDelay()) {
            $delay = $this->policy->retryDelayMilliseconds($failures);
        }
        $application->context()->logger()->log('warning', 'worker retry scheduled', [
            'consecutive_failures' => $failures,
            'delay_ms' => $delay,
            'error' => $reason,
        ]);
        $this->waitIfRunning($application, $delay);
        return true;
    }

    private function waitIfRunning(Application $application, int $delayMilliseconds): void
    {
        if ($delayMilliseconds > 0 && !$this->shouldStop($application)) {
            $this->sleeper->sleepMilliseconds($delayMilliseconds);
        }
    }

    private function shouldStop(Application $application): bool
    {
        return $this->stopRequested
            || $this->signals->stopRequested()
            || $application->context()->cancellation()->isCancellationRequested();
    }
}
