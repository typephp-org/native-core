<?php

namespace TypePHP\NativeCore\Host\Worker;

final class WorkerPolicy
{
    private int $idleMilliseconds;
    private int $initialRetryMilliseconds;
    private int $maximumRetryMilliseconds;
    private int $maximumConsecutiveFailures;

    public function __construct(
        int $idleMilliseconds,
        int $initialRetryMilliseconds,
        int $maximumRetryMilliseconds,
        int $maximumConsecutiveFailures
    ) {
        if ($idleMilliseconds < 0 || $initialRetryMilliseconds < 0 || $maximumRetryMilliseconds < 0) {
            throw new \InvalidArgumentException('Worker policy delays must not be negative');
        }
        if ($maximumRetryMilliseconds < $initialRetryMilliseconds) {
            throw new \InvalidArgumentException('Maximum retry delay must be at least the initial retry delay');
        }
        if ($maximumConsecutiveFailures < 0) {
            throw new \InvalidArgumentException('Maximum consecutive failures must not be negative');
        }

        $this->idleMilliseconds = $idleMilliseconds;
        $this->initialRetryMilliseconds = $initialRetryMilliseconds;
        $this->maximumRetryMilliseconds = $maximumRetryMilliseconds;
        $this->maximumConsecutiveFailures = $maximumConsecutiveFailures;
    }

    public static function production(): self
    {
        return new self(1000, 250, 30000, 10);
    }

    public function idleMilliseconds(): int { return $this->idleMilliseconds; }
    public function maximumConsecutiveFailures(): int { return $this->maximumConsecutiveFailures; }

    public function retryDelayMilliseconds(int $consecutiveFailures): int
    {
        if ($consecutiveFailures <= 1) {
            return $this->initialRetryMilliseconds;
        }

        $delay = $this->initialRetryMilliseconds;
        for ($attempt = 1; $attempt < $consecutiveFailures; $attempt++) {
            if ($delay >= $this->maximumRetryMilliseconds) {
                return $this->maximumRetryMilliseconds;
            }
            $delay *= 2;
            if ($delay >= $this->maximumRetryMilliseconds) {
                return $this->maximumRetryMilliseconds;
            }
        }
        return $delay;
    }
}
