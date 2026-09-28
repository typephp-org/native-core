<?php

namespace TypePHP\NativeCore\Host\Worker;

final class WorkerHealth
{
    public const STARTING = 'starting';
    public const RUNNING = 'running';
    public const RETRYING = 'retrying';
    public const STOPPING = 'stopping';
    public const STOPPED = 'stopped';
    public const FAILED = 'failed';

    private string $state;
    private int $successfulRuns;
    private int $totalFailures;
    private int $consecutiveFailures;
    private string $lastError;

    public function __construct()
    {
        $this->state = self::STARTING;
        $this->successfulRuns = 0;
        $this->totalFailures = 0;
        $this->consecutiveFailures = 0;
        $this->lastError = '';
    }

    public function markRunning(): void { $this->state = self::RUNNING; }
    public function markStopping(): void { $this->state = self::STOPPING; }
    public function markStopped(): void { $this->state = self::STOPPED; }
    public function markFailed(): void { $this->state = self::FAILED; }

    public function recordSuccess(): void
    {
        $this->successfulRuns++;
        $this->consecutiveFailures = 0;
        $this->lastError = '';
        $this->state = self::RUNNING;
    }

    public function recordFailure(string $error): void
    {
        $this->totalFailures++;
        $this->consecutiveFailures++;
        $this->lastError = $error;
        $this->state = self::RETRYING;
    }

    public function state(): string { return $this->state; }
    public function successfulRuns(): int { return $this->successfulRuns; }
    public function totalFailures(): int { return $this->totalFailures; }
    public function consecutiveFailures(): int { return $this->consecutiveFailures; }
    public function lastError(): string { return $this->lastError; }
    public function isReady(): bool { return $this->state === self::RUNNING; }
    public function isLive(): bool { return $this->state !== self::FAILED && $this->state !== self::STOPPED; }
}
